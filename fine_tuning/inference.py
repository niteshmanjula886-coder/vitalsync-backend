"""
VitalSync AI - Conversational Inference Interface
Lightweight Python inference interface for the fine-tuned LoRA conversational model.
Designed for clean, decoupled integration and ready for FastAPI microservices.
"""

import os
import sys
import json
import argparse

DEFAULT_SYSTEM_PROMPT = (
    "You are VitalSync AI, a natural, supportive, and attentive conversational partner. "
    "Communicate naturally, maintain context, and provide thoughtful, coherent dialogue."
)

class VitalSyncDialogueEngine:
    def __init__(self, adapter_dir=None, base_model=None, device="auto"):
        import torch
        from transformers import AutoTokenizer, AutoModelForCausalLM
        from peft import PeftModel

        cur_dir = os.path.dirname(os.path.abspath(__file__))
        self.adapter_dir = adapter_dir or os.path.join(cur_dir, "adapters", "vitalsync_dailydialog_lora")
        
        # Determine base model from manifest or parameter
        manifest_path = os.path.join(self.adapter_dir, "training_manifest.json")
        if base_model:
            self.base_model_name = base_model
        elif os.path.exists(manifest_path):
            with open(manifest_path, "r", encoding="utf-8") as f:
                self.base_model_name = json.load(f).get("base_model", "Qwen/Qwen2.5-0.5B-Instruct")
        else:
            self.base_model_name = "Qwen/Qwen2.5-0.5B-Instruct"

        if device == "auto":
            self.device = "cuda" if torch.cuda.is_available() and torch.cuda.get_device_properties(0).total_memory > (1.8 * 1024**3) else "cpu"
        else:
            self.device = device

        print(f"[ENGINE] Initializing VitalSync Dialogue Engine on {self.device.upper()}...")
        print(f"[ENGINE] Base Model: {self.base_model_name}")
        print(f"[ENGINE] Adapter Dir: {self.adapter_dir}")

        # Load Tokenizer
        tokenizer_src = self.adapter_dir if os.path.exists(os.path.join(self.adapter_dir, "tokenizer_config.json")) else self.base_model_name
        self.tokenizer = AutoTokenizer.from_pretrained(tokenizer_src, trust_remote_code=True)
        if self.tokenizer.pad_token is None:
            self.tokenizer.pad_token = self.tokenizer.eos_token

        # Load Base Model
        torch_dtype = torch.float32 if self.device == "cpu" else torch.float16
        raw_base = AutoModelForCausalLM.from_pretrained(
            self.base_model_name,
            torch_dtype=torch_dtype,
            trust_remote_code=True,
            low_cpu_mem_usage=True
        ).to(self.device)

        # Attach LoRA adapter if present
        if os.path.exists(os.path.join(self.adapter_dir, "adapter_config.json")):
            print("[ENGINE] Attaching fine-tuned LoRA adapter...")
            self.model = PeftModel.from_pretrained(raw_base, self.adapter_dir).to(self.device)
        else:
            print("[WARN] LoRA adapter weights not found; running with base model.")
            self.model = raw_base

        self.model.eval()
        print("[ENGINE] Model loaded and ready for conversational inference.")

    def generate_response(self, user_message, history=None, system_prompt=DEFAULT_SYSTEM_PROMPT, max_new_tokens=120, temperature=0.7, top_p=0.9):
        import torch

        messages = []
        if system_prompt:
            messages.append({"role": "system", "content": system_prompt})

        # Append multi-turn history if provided
        if history and isinstance(history, list):
            for h in history:
                messages.append({"role": h.get("role", "user"), "content": h.get("content", "")})

        messages.append({"role": "user", "content": user_message})

        if hasattr(self.tokenizer, "apply_chat_template"):
            prompt = self.tokenizer.apply_chat_template(messages, tokenize=False, add_generation_prompt=True)
        else:
            prompt = "".join([f"<|im_start|>{m['role']}\n{m['content']}<|im_end|>\n" for m in messages]) + "<|im_start|>assistant\n"

        inputs = self.tokenizer(prompt, return_tensors="pt").to(self.device)
        input_len = inputs["input_ids"].size(1)

        with torch.no_grad():
            output_tokens = self.model.generate(
                **inputs,
                max_new_tokens=max_new_tokens,
                temperature=temperature,
                top_p=top_p,
                do_sample=True,
                pad_token_id=self.tokenizer.pad_token_id,
                eos_token_id=self.tokenizer.eos_token_id
            )

        new_tokens = output_tokens[0][input_len:]
        reply = self.tokenizer.decode(new_tokens, skip_special_tokens=True).strip()
        return reply

def interactive_chat(engine):
    print("=" * 65)
    print("💬 VitalSync AI Interactive Chat (type 'quit' or 'exit' to stop)")
    print("=" * 65)
    history = []
    while True:
        try:
            user_input = input("\nYou: ").strip()
            if not user_input:
                continue
            if user_input.lower() in ("quit", "exit"):
                break

            reply = engine.generate_response(user_input, history=history)
            print(f"VitalSync AI: {reply}")

            history.append({"role": "user", "content": user_input})
            history.append({"role": "assistant", "content": reply})

            # Keep rolling history to last 6 turns
            if len(history) > 6:
                history = history[-6:]
        except KeyboardInterrupt:
            break

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="VitalSync Conversational Inference")
    parser.add_argument("--query", type=str, default=None, help="One-off query to test")
    parser.add_argument("--interactive", action="store_true", help="Launch interactive CLI chat session")
    args = parser.parse_args()

    engine = VitalSyncDialogueEngine()
    if args.query:
        print(f"\nUser: {args.query}")
        response = engine.generate_response(args.query)
        print(f"VitalSync AI: {response}")
    elif args.interactive or len(sys.argv) == 1:
        interactive_chat(engine)

