"""
VitalSync AI - DailyDialog Fine-Tuning Pipeline: Training Engine
Fine-tunes a pretrained conversational base model using Hugging Face Transformers & PEFT LoRA.
Keeps the base model weights frozen and saves the lightweight adapter separately.
"""

import os
import sys
import json
import argparse
import math
import time

def parse_args():
    parser = argparse.ArgumentParser(description="Fine-tune VitalSync Conversational Model with LoRA")
    parser.add_argument("--base_model", type=str, default="Qwen/Qwen2.5-0.5B-Instruct", help="Pretrained conversational base model")
    parser.add_argument("--fallback_model", type=str, default="microsoft/DialoGPT-small", help="Fallback lightweight base model")
    parser.add_argument("--train_file", type=str, default=None, help="Path to train.jsonl")
    parser.add_argument("--val_file", type=str, default=None, help="Path to val.jsonl")
    parser.add_argument("--output_adapter_dir", type=str, default=None, help="Directory to save LoRA adapter")
    parser.add_argument("--checkpoint_dir", type=str, default=None, help="Directory for training checkpoints")
    parser.add_argument("--epochs", type=int, default=2, help="Number of training epochs")
    parser.add_argument("--batch_size", type=int, default=2, help="Per device batch size")
    parser.add_argument("--grad_accum", type=int, default=4, help="Gradient accumulation steps")
    parser.add_argument("--lr", type=float, default=2e-4, help="Peak learning rate")
    parser.add_argument("--max_seq_len", type=int, default=512, help="Maximum sequence length")
    parser.add_argument("--lora_r", type=int, default=8, help="LoRA rank")
    parser.add_argument("--lora_alpha", type=int, default=16, help="LoRA alpha scaling factor")
    parser.add_argument("--lora_dropout", type=float, default=0.05, help="LoRA dropout rate")
    parser.add_argument("--max_samples", type=int, default=1000, help="Subset size for fast reproducible training on constrained hardware")
    parser.add_argument("--device", type=str, default="auto", choices=["auto", "cpu", "cuda"], help="Computation device")
    return parser.parse_args()

def setup_paths(args):
    cur_dir = os.path.dirname(os.path.abspath(__file__))
    if not args.train_file:
        args.train_file = os.path.join(cur_dir, "datasets", "processed", "train.jsonl")
    if not args.val_file:
        args.val_file = os.path.join(cur_dir, "datasets", "processed", "val.jsonl")
    if not args.output_adapter_dir:
        args.output_adapter_dir = os.path.join(cur_dir, "adapters", "vitalsync_dailydialog_lora")
    if not args.checkpoint_dir:
        args.checkpoint_dir = os.path.join(cur_dir, "checkpoints")

    os.makedirs(args.output_adapter_dir, exist_ok=True)
    os.makedirs(args.checkpoint_dir, exist_ok=True)

def load_jsonl_conversations(file_path, max_samples=None):
    conversations = []
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"Processed file not found: {file_path}. Run prepare_dataset.py first.")
    
    with open(file_path, "r", encoding="utf-8") as f:
        for idx, line in enumerate(f):
            if max_samples and idx >= max_samples:
                break
            line = line.strip()
            if line:
                conversations.append(json.loads(line))
    return conversations

def train_pipeline(args):
    setup_paths(args)

    print("=" * 65)
    print("🚀 VitalSync AI — Parameter-Efficient Fine-Tuning Pipeline (LoRA)")
    print("=" * 65)
    print(f"Base Model:       {args.base_model}")
    print(f"Train File:       {args.train_file}")
    print(f"Val File:         {args.val_file}")
    print(f"Output Adapter:   {args.output_adapter_dir}")
    print(f"Max Samples:      {args.max_samples or 'All'}")
    print(f"Epochs:           {args.epochs}")
    print(f"Effective Batch:  {args.batch_size * args.grad_accum}")
    print(f"LoRA Rank (r):    {args.lora_r} (alpha={args.lora_alpha})")
    print("=" * 65)

    import torch
    from transformers import (
        AutoTokenizer,
        AutoModelForCausalLM,
        TrainingArguments,
        Trainer,
        DataCollatorForSeq2Seq
    )
    from peft import LoraConfig, get_peft_model, TaskType
    from datasets import Dataset

    # Select hardware device
    if args.device == "auto":
        device = "cuda" if torch.cuda.is_available() and torch.cuda.get_device_properties(0).total_memory > (1.8 * 1024**3) else "cpu"
    else:
        device = args.device

    print(f"[HARDWARE] Selected execution device: {device.upper()}")
    if device == "cuda":
        print(f"[HARDWARE] GPU: {torch.cuda.get_device_name(0)} (VRAM: {torch.cuda.get_device_properties(0).total_memory / (1024**3):.2f} GB)")
    else:
        print(f"[HARDWARE] CPU Threads: {torch.get_num_threads()} | RAM-Optimized Execution")

    # Load Tokenizer & Model
    model_name = args.base_model
    try:
        print(f"[MODEL] Loading tokenizer for {model_name}...")
        tokenizer = AutoTokenizer.from_pretrained(model_name, trust_remote_code=True)
    except Exception as e:
        print(f"[WARN] Failed loading {model_name} ({e}). Falling back to {args.fallback_model}...")
        model_name = args.fallback_model
        tokenizer = AutoTokenizer.from_pretrained(model_name)

    if tokenizer.pad_token is None:
        tokenizer.pad_token = tokenizer.eos_token

    print(f"[MODEL] Loading base model weights: {model_name}...")
    torch_dtype = torch.float32 if device == "cpu" else torch.float16
    base_model = AutoModelForCausalLM.from_pretrained(
        model_name,
        torch_dtype=torch_dtype,
        trust_remote_code=True,
        low_cpu_mem_usage=True
    )

    # Freeze base model weights
    for param in base_model.parameters():
        param.requires_grad = False

    # Configure LoRA targets
    target_modules = ["q_proj", "v_proj", "k_proj", "o_proj"]
    # Check if model uses GPT-2 style conv1d
    if hasattr(base_model, "transformer") and not hasattr(base_model, "model"):
        target_modules = ["c_attn", "c_proj"]

    lora_config = LoraConfig(
        task_type=TaskType.CAUSAL_LM,
        r=args.lora_r,
        lora_alpha=args.lora_alpha,
        lora_dropout=args.lora_dropout,
        target_modules=target_modules,
        bias="none"
    )

    model = get_peft_model(base_model, lora_config)
    trainable_params, all_param = model.get_nb_trainable_parameters()
    print(f"[PEFT] Trainable parameters: {trainable_params:,} / {all_param:,} ({100 * trainable_params / all_param:.2f}%)")

    # Prepare Conversational Data
    print("[DATA] Tokenizing multi-turn conversations into instruction sequences...")
    train_convs = load_jsonl_conversations(args.train_file, max_samples=args.max_samples)
    val_convs = load_jsonl_conversations(args.val_file, max_samples=max(50, int((args.max_samples or 1000) * 0.1)))

    def format_chat_item(example):
        messages = example["messages"]
        if hasattr(tokenizer, "apply_chat_template"):
            text = tokenizer.apply_chat_template(messages, tokenize=False, add_generation_prompt=False)
        else:
            # Fallback ChatML formatter
            text = ""
            for m in messages:
                text += f"<|im_start|>{m['role']}\n{m['content']}<|im_end|>\n"
        
        encoded = tokenizer(
            text,
            max_length=args.max_seq_len,
            truncation=True,
            padding=False
        )
        encoded["labels"] = list(encoded["input_ids"])
        return encoded

    train_dataset = Dataset.from_list(train_convs).map(format_chat_item, remove_columns=list(train_convs[0].keys()))
    val_dataset = Dataset.from_list(val_convs).map(format_chat_item, remove_columns=list(val_convs[0].keys()))

    print(f"[DATA] Prepared {len(train_dataset)} training dialogues and {len(val_dataset)} validation dialogues.")

    # Training Arguments
    training_args = TrainingArguments(
        output_dir=args.checkpoint_dir,
        num_train_epochs=args.epochs,
        per_device_train_batch_size=args.batch_size,
        per_device_eval_batch_size=args.batch_size,
        gradient_accumulation_steps=args.grad_accum,
        learning_rate=args.lr,
        warmup_ratio=0.05,
        logging_steps=10,
        eval_strategy="epoch",
        save_strategy="epoch",
        save_total_limit=1,
        fp16=(device == "cuda"),
        report_to="none",
        dataloader_num_workers=0
    )

    data_collator = DataCollatorForSeq2Seq(tokenizer, pad_to_multiple_of=8, return_tensors="pt")

    trainer = Trainer(
        model=model,
        args=training_args,
        train_dataset=train_dataset,
        eval_dataset=val_dataset,
        tokenizer=tokenizer,
        data_collator=data_collator
    )

    print("\n[TRAINING] Starting LoRA fine-tuning loop...")
    start_time = time.time()
    train_result = trainer.train()
    duration = time.time() - start_time

    metrics = train_result.metrics
    print(f"\n[OK] Training completed in {duration:.2f} seconds ({duration / 60:.2f} mins).")
    print(f"[METRICS] Train Loss: {metrics.get('train_loss', 0.0):.4f}")

    # Evaluate validation loss
    print("[EVALUATION] Computing validation set loss & perplexity...")
    eval_metrics = trainer.evaluate()
    val_loss = eval_metrics.get("eval_loss", 0.0)
    perplexity = math.exp(val_loss) if val_loss < 20 else float("inf")
    print(f"[METRICS] Validation Loss: {val_loss:.4f} | Validation Perplexity: {perplexity:.2f}")

    # Save ONLY the fine-tuned LoRA adapter and tokenizer
    print(f"[SAVING] Exporting fine-tuned LoRA adapter to: {args.output_adapter_dir}")
    model.save_pretrained(args.output_adapter_dir)
    tokenizer.save_pretrained(args.output_adapter_dir)

    # Save Training Manifest
    manifest = {
        "base_model": model_name,
        "adapter_type": "LoRA",
        "lora_r": args.lora_r,
        "lora_alpha": args.lora_alpha,
        "lora_dropout": args.lora_dropout,
        "trainable_parameters": trainable_params,
        "total_parameters": all_param,
        "trainable_percent": round(100 * trainable_params / all_param, 2),
        "epochs": args.epochs,
        "train_loss": round(metrics.get("train_loss", 0.0), 4),
        "val_loss": round(val_loss, 4),
        "perplexity": round(perplexity, 2),
        "duration_seconds": round(duration, 2),
        "target_device": device,
        "completed_at": time.strftime("%Y-%m-%d %H:%M:%S")
    }

    manifest_path = os.path.join(args.output_adapter_dir, "training_manifest.json")
    with open(manifest_path, "w", encoding="utf-8") as f:
        json.dump(manifest, f, indent=2)

    print(f"[SAVED] Training manifest written to: {manifest_path}")
    print("=" * 65)
    print("🎯 LoRA Fine-Tuning Successfully Finished!")
    print("=" * 65)

if __name__ == "__main__":
    train_pipeline(parse_args())

