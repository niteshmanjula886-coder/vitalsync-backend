# VitalSync AI — DailyDialog Conversational Fine-Tuning Pipeline

A reproducible, production-grade Parameter-Efficient Fine-Tuning (PEFT) pipeline using **LoRA (Low-Rank Adaptation)** and Hugging Face `transformers` on the DailyDialog conversational dataset.

---

## 1. Project Architecture

```
fine_tuning/
├── datasets/
│   ├── raw/
│   │   └── data/
│   │       ├── dialogues.json         # Raw DailyDialog dataset (13,118 conversations)
│   │       └── ontology.json          # Dialogue acts and domain definitions
│   └── processed/
│       ├── train.jsonl                # Deduplicated conversational training split (10,384 diags)
│       ├── val.jsonl                  # Deduplicated validation split (904 diags)
│       ├── test.jsonl                 # Deduplicated unseen test split (868 diags)
│       └── dataset_summary.json       # Preprocessing and domain/emotion metrics
├── adapters/
│   └── vitalsync_dailydialog_lora/    # Saved fine-tuned LoRA adapter weights and config
├── prepare_dataset.py                 # Multi-turn conversational format converter and deduplicator
├── train.py                           # PyTorch + Transformers + PEFT LoRA training loop
├── evaluate.py                        # Perplexity, loss tracking, and before/after comparisons
├── inference.py                       # Standalone Python inference interface for FastAPI integration
├── requirements.txt                   # Dependency manifest
└── README.md                          # Documentation and reproducibility guide
```

---

## 2. Dataset Statistics & Structure

- **Raw Dialogues:** 13,118 dialogues (102,979 turns).
- **Deduplicated & Cleaned Splits:**
  - **Train:** 10,384 dialogues (81,574 turns)
  - **Validation:** 904 dialogues (7,287 turns)
  - **Test (Unseen):** 868 dialogues (6,771 turns)
  - **Duplicates Removed:** 962 conversations (zero overlap between splits).
- **Dominant Domains:** Relationship (4,335), Ordinary Life (3,738), Work (1,868), Tourism (1,060), Attitude & Emotion (700), Health (262), Finance (483), School Life (488).
- **Emotion Distribution:** Happiness (12,885), Neutral/No Emotion (85,572), Surprise (1,823), Sadness (1,150), Anger (1,022), Disgust (353), Fear (174).
- **Speech Act Distribution:** Inform (46,532), Question (29,428), Directive (17,295), Commissive (9,724).

---

## 3. Important Dataset Licensing Notice

> **⚠️ NON-COMMERCIAL RESEARCH LICENCE:**
> The DailyDialog dataset was compiled by Li et al. (IJCNLP 2017) from language-learning websites and is distributed under the **Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)** licence.
>
> **Commercial Restriction:** This dataset **cannot** be used directly for commercial product deployments.
> **Reproducibility Guarantee:** This fine-tuning pipeline is modular and decoupled from the raw data format. When preparing for commercial production, you can replace `datasets/raw/data/dialogues.json` with commercially permissive datasets (such as **OpenAssistant Conversations**, **UltraChat**, or custom VitalSync synthetic telemetry dialogues) without changing the training code.

---

## 4. Hardware Configuration & Base Model Selection

- **System Hardware:** 12-Core CPU, 16 GB System RAM, NVIDIA GeForce GT 1030 (2GB VRAM, ~800MB usable VRAM).
- **Base Model Choice:** `Qwen/Qwen2.5-0.5B-Instruct` (or `microsoft/DialoGPT-small`).
  - **Why not a 7B or 3B model?** Pretrained 3B/7B models require 6 GB to 16 GB of dedicated GPU VRAM. The GT 1030 would immediately fail with CUDA Out-Of-Memory.
  - **Why 0.5B with LoRA?** It fits comfortably in memory, features modern ChatML conversational templates, and LoRA only trains ~1.2M parameters (<15 MB adapter), leaving the original base model weights completely frozen and unaltered.

---

## 5. Quickstart & Execution Guide

### Step 1: Install Dependencies
```bash
pip install -r requirements.txt
```

### Step 2: Prepare & Validate Datasets
```bash
python prepare_dataset.py
```

### Step 3: Train LoRA Adapter
```bash
python train.py --epochs 3 --lr 2e-4 --batch_size 4 --accum 2
```

### Step 4: Evaluate on Unseen Test Split
```bash
python evaluate.py
```

### Step 5: Test Conversational Inference
```bash
python inference.py --query "I had a really tiring day at work today."
```

---

## 6. Integration Roadmap (FastAPI Backend)

The standalone inference engine (`inference.py`) exposes a simple `generate_response(messages)` method ready to be mounted into a FastAPI microservice:

```python
from fastapi import FastAPI
from inference import VitalSyncDialogueEngine

app = FastAPI()
engine = VitalSyncDialogueEngine()

@app.post("/chat")
def chat_endpoint(user_message: str):
    response = engine.generate_response(user_message)
    return {"reply": response}
```
Existing VitalSync Android UI and PHP endpoints remain completely unchanged and safe.

