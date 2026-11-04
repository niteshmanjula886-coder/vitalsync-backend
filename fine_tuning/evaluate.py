"""
VitalSync AI - DailyDialog Fine-Tuning Pipeline: Evaluation Suite
Evaluates the fine-tuned LoRA model on the unseen test split (test.jsonl).
Produces before-and-after comparisons, perplexity metrics, and conversational coherence tests.
"""

import os
import sys
import json
import argparse
import math
import time

def parse_args():
    parser = argparse.ArgumentParser(description="Evaluate VitalSync LoRA Fine-Tuned Model")
    parser.add_argument("--adapter_dir", type=str, default=None, help="Directory of fine-tuned LoRA adapter")
    parser.add_argument("--test_file", type=str, default=None, help="Path to test.jsonl")
    parser.add_argument("--num_eval_samples", type=int, default=100, help="Number of test conversations for loss calculation")
    parser.add_argument("--num_qualitative_tests", type=int, default=5, help="Number of before-after comparison tests")
    parser.add_argument("--device", type=str, default="auto", choices=["auto", "cpu", "cuda"])
    return parser.parse_args()

def evaluate_pipeline(args):
    cur_dir = os.path.dirname(os.path.abspath(__file__))
    if not args.adapter_dir:
        args.adapter_dir = os.path.join(cur_dir, "adapters", "vitalsync_dailydialog_lora")
    if not args.test_file:
        args.test_file = os.path.join(cur_dir, "datasets", "processed", "test.jsonl")

    manifest_path = os.path.join(args.adapter_dir, "training_manifest.json")
    base_model_name = "Qwen/Qwen2.5-0.5B-Instruct"
    if os.path.exists(manifest_path):
        with open(manifest_path, "r", encoding="utf-8") as f:
            manifest = json.load(f)
            base_model_name = manifest.get("base_model", base_model_name)

    print("=" * 65)
    print("🧪 VitalSync AI — Model Evaluation & Before/After Comparison")
    print("=" * 65)
    print(f"Base Model:       {base_model_name}")
    print(f"Adapter:          {args.adapter_dir}")
    print(f"Test Split File:  {args.test_file}")
    print("=" * 65)

    import torch
    from transformers import AutoTokenizer, AutoModelForCausalLM
    from peft import PeftModel

    if args.device == "auto":
        device = "cuda" if torch.cuda.is_available() and torch.cuda.get_device_properties(0).total_memory > (1.8 * 1024**3) else "cpu"
    else:
        device = args.device

    print(f"[DEVICE] Evaluation executing on: {device.upper()}")

    # 1. Load Base Model and Tokenizer
    print("[MODEL] Loading base model for comparison baseline...")
    tokenizer = AutoTokenizer.from_pretrained(args.adapter_dir, trust_remote_code=True)
    if tokenizer.pad_token is None:
        tokenizer.pad_token = tokenizer.eos_token

    torch_dtype = torch.float32 if device == "cpu" else torch.float16
    base_model = AutoModelForCausalLM.from_pretrained(
        base_model_name,
        torch_dtype=torch_dtype,
        trust_remote_code=True,
        low_cpu_mem_usage=True
    ).to(device)
    base_model.eval()

    # 2. Load Fine-Tuned LoRA Model
    print("[PEFT] Loading LoRA fine-tuned adapter weights...")
    lora_model = PeftModel.from_pretrained(base_model, args.adapter_dir).to(device)
    lora_model.eval()

    # 3. Quantitative Test Evaluation (Perplexity on Unseen test.jsonl)
    print(f"[METRICS] Evaluating perplexity on {args.num_eval_samples} unseen test dialogues...")
    test_conversations = []
    with open(args.test_file, "r", encoding="utf-8") as f:
        for idx, line in enumerate(f):
            if idx >= args.num_eval_samples:
                break
            line = line.strip()
            if line:
                test_conversations.append(json.loads(line))

    total_loss = 0.0
    total_tokens = 0

    with torch.no_grad():
        for item in test_conversations:
            messages = item["messages"]
            if hasattr(tokenizer, "apply_chat_template"):
                text = tokenizer.apply_chat_template(messages, tokenize=False, add_generation_prompt=False)
            else:
                text = "".join([f"<|im_start|>{m['role']}\n{m['content']}<|im_end|>\n" for m in messages])
            
            inputs = tokenizer(text, max_length=512, truncation=True, return_tensors="pt").to(device)
            labels = inputs["input_ids"].clone()
            outputs = lora_model(**inputs, labels=labels)
            loss = outputs.loss.item()
            num_tokens = inputs["input_ids"].size(1)
            total_loss += loss * num_tokens
            total_tokens += num_tokens

    avg_test_loss = total_loss / max(1, total_tokens)
    test_perplexity = math.exp(avg_test_loss) if avg_test_loss < 20 else float("inf")

    print(f"[TEST RESULTS] Test Loss: {avg_test_loss:.4f} | Test Perplexity: {test_perplexity:.2f}")

    # 4. Qualitative Before-and-After Response Generation
    print("\n" + "=" * 65)
    print("💬 Qualitative Conversational Benchmarks: Before vs After Fine-Tuning")
    print("=" * 65)

    benchmark_dialogues = [
        [
            {"role": "user", "content": "I've been feeling pretty burned out with work lately. How do you usually handle stress?"}
        ],
        [
            {"role": "user", "content": "Would you like to go to that new Italian restaurant downtown tonight?"},
            {"role": "assistant", "content": "I'd love to! What time were you thinking?"},
            {"role": "user", "content": "Around 7:30 PM. I hear their pasta is handmade daily."}
        ],
        [
            {"role": "user", "content": "I'm thinking about starting a regular running routine, but I always quit after two days."}
        ],
        [
            {"role": "user", "content": "Did you see the final game last night? The ending was unreal!"}
        ]
    ]

    comparisons = []

    def generate(model, messages):
        if hasattr(tokenizer, "apply_chat_template"):
            prompt = tokenizer.apply_chat_template(messages, tokenize=False, add_generation_prompt=True)
        else:
            prompt = "".join([f"<|im_start|>{m['role']}\n{m['content']}<|im_end|>\n" for m in messages]) + "<|im_start|>assistant\n"

        inputs = tokenizer(prompt, return_tensors="pt").to(device)
        with torch.no_grad():
            output_tokens = model.generate(
                **inputs,
                max_new_tokens=100,
                temperature=0.7,
                top_p=0.9,
                do_sample=True,
                pad_token_id=tokenizer.pad_token_id,
                eos_token_id=tokenizer.eos_token_id
            )
        new_tokens = output_tokens[0][inputs["input_ids"].size(1):]
        return tokenizer.decode(new_tokens, skip_special_tokens=True).strip()

    for idx, test_msgs in enumerate(benchmark_dialogues):
        user_query = test_msgs[-1]["content"]
        
        # Generation with base model
        with lora_model.disable_adapter():
            base_reply = generate(base_model, test_msgs)

        # Generation with fine-tuned LoRA model
        lora_reply = generate(lora_model, test_msgs)

        print(f"\n[Scenario {idx+1}] User Query: \"{user_query}\"")
        print(f"  🔹 [Before / Base Model]:     {base_reply}")
        print(f"  ✨ [After / Fine-Tuned LoRA]: {lora_reply}")

        comparisons.append({
            "scenario": idx + 1,
            "dialogue_history": test_msgs,
            "base_response": base_reply,
            "lora_fine_tuned_response": lora_reply
        })

    # Save Evaluation Artifact
    eval_report = {
        "test_loss": round(avg_test_loss, 4),
        "test_perplexity": round(test_perplexity, 2),
        "eval_samples_count": len(test_conversations),
        "comparisons": comparisons,
        "evaluated_at": time.strftime("%Y-%m-%d %H:%M:%S")
    }

    report_path = os.path.join(args.adapter_dir, "evaluation_results.json")
    with open(report_path, "w", encoding="utf-8") as f:
        json.dump(eval_report, f, indent=2, ensure_ascii=False)

    print(f"\n[REPORT] Detailed evaluation saved to: {report_path}")
    print("=" * 65)

if __name__ == "__main__":
    evaluate_pipeline(parse_args())

