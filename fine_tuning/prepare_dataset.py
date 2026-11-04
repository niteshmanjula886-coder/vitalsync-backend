"""
VitalSync AI - DailyDialog Fine-Tuning Pipeline: Dataset Preparation
Extracts, inspects, validates, deduplicates, and formats multi-turn conversational datasets.
Generates instruction-tuning ready train.jsonl, val.jsonl, and test.jsonl files.
"""

import os
import json
import hashlib
from collections import Counter, defaultdict

RAW_DIR = os.path.join(os.path.dirname(__file__), "datasets", "raw", "data")
PROCESSED_DIR = os.path.join(os.path.dirname(__file__), "datasets", "processed")

SYSTEM_PROMPT = (
    "You are VitalSync AI, a natural, empathetic, and attentive conversational partner. "
    "Maintain context across multiple conversational turns, understand emotional tone, "
    "and respond naturally, constructively, and coherently."
)

def inspect_and_clean_dialogues(raw_dialogues_path):
    if not os.path.exists(raw_dialogues_path):
        raise FileNotFoundError(f"Raw dialogues file not found at: {raw_dialogues_path}")

    with open(raw_dialogues_path, "r", encoding="utf-8") as f:
        raw_data = json.load(f)

    print("=" * 65)
    print(f"[INSPECT] Step 1: Raw Dataset Inspection ({len(raw_data)} dialogues)")
    print("=" * 65)

    seen_hashes = set()
    split_records = {"train": [], "validation": [], "test": []}
    duplicate_within = 0
    duplicate_across = 0
    empty_records = 0
    total_raw_turns = 0

    emotion_counter = Counter()
    intent_counter = Counter()
    domain_counter = Counter()

    for item in raw_data:
        split = item.get("data_split", "train")
        did = item.get("dialogue_id", "")
        domains = item.get("domains", ["General"])
        turns = item.get("turns", [])
        total_raw_turns += len(turns)

        # Validate turns
        cleaned_turns = []
        for t in turns:
            utt = t.get("utterance", "").strip()
            if not utt:
                continue
            
            # Extract speaker: 'user' -> 'user', 'system' -> 'assistant'
            spk = t.get("speaker", "user").lower()
            role = "assistant" if spk in ("system", "assistant") else "user"
            
            emotion = t.get("emotion", "no emotion")
            emotion_counter[emotion] += 1
            
            # Extract intent / dialogue act
            acts = t.get("dialogue_acts", {}).get("binary", [])
            intent = acts[0].get("intent", "inform") if acts else "inform"
            intent_counter[intent] += 1

            cleaned_turns.append({
                "role": role,
                "content": utt,
                "emotion": emotion,
                "intent": intent
            })

        if len(cleaned_turns) < 2:
            empty_records += 1
            continue

        domain_name = domains[0] if domains else "General"
        domain_counter[domain_name] += 1

        # Check content-based deduplication across splits
        conv_fingerprint = " [SEP] ".join([t["content"].lower() for t in cleaned_turns])
        h = hashlib.sha256(conv_fingerprint.encode("utf-8")).hexdigest()

        if h in seen_hashes:
            # Check if duplicate is within the same split or cross-split
            duplicate_across += 1
            continue

        seen_hashes.add(h)

        # Standard Conversational Instruction-Tuning Format
        formatted_messages = [{"role": "system", "content": SYSTEM_PROMPT}]
        for t in cleaned_turns:
            formatted_messages.append({
                "role": t["role"],
                "content": t["content"]
            })

        example = {
            "dialogue_id": did,
            "split": split,
            "domain": domain_name,
            "turns_count": len(cleaned_turns),
            "messages": formatted_messages,
            "turns_metadata": cleaned_turns
        }

        if split in split_records:
            split_records[split].append(example)
        else:
            split_records["train"].append(example)

    return split_records, {
        "total_raw_dialogues": len(raw_data),
        "total_raw_turns": total_raw_turns,
        "empty_or_too_short": empty_records,
        "duplicates_removed": duplicate_across,
        "emotions": dict(emotion_counter),
        "intents": dict(intent_counter),
        "domains": dict(domain_counter)
    }

def save_jsonl_splits(split_records, output_dir=PROCESSED_DIR):
    os.makedirs(output_dir, exist_ok=True)
    stats = {}

    split_file_map = {
        "train": "train.jsonl",
        "validation": "val.jsonl",
        "test": "test.jsonl"
    }

    for split_key, filename in split_file_map.items():
        records = split_records[split_key]
        out_path = os.path.join(output_dir, filename)
        
        total_messages = 0
        total_chars = 0
        
        with open(out_path, "w", encoding="utf-8") as f:
            for item in records:
                # Calculate turn statistics
                turns_count = len(item["messages"]) - 1 # exclude system
                total_messages += turns_count
                for m in item["messages"]:
                    total_chars += len(m["content"])
                
                f.write(json.dumps(item, ensure_ascii=False) + "\n")

        stats[split_key] = {
            "dialogues_count": len(records),
            "messages_count": total_messages,
            "avg_turns_per_dialogue": round(total_messages / max(1, len(records)), 2),
            "avg_chars_per_message": round(total_chars / max(1, total_messages), 2),
            "file_path": out_path
        }
        print(f"[SAVED] {len(records):,} clean dialogues ({total_messages:,} turns) to: {filename}")

    return stats

def main():
    raw_path = os.path.join(RAW_DIR, "dialogues.json")
    split_records, inspection_stats = inspect_and_clean_dialogues(raw_path)

    print("\n" + "=" * 65)
    print("[PROCESS] Step 2: Saving Cleaned, Non-Overlapping JSONL Datasets")
    print("=" * 65)
    split_stats = save_jsonl_splits(split_records, PROCESSED_DIR)

    summary = {
        "inspection_metadata": inspection_stats,
        "processed_splits": split_stats
    }

    summary_path = os.path.join(PROCESSED_DIR, "dataset_summary.json")
    with open(summary_path, "w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2, ensure_ascii=False)

    print(f"\n[SUMMARY] Full dataset summary metrics saved to: {summary_path}")
    print("=" * 65)
    print(f"Train:      {split_stats['train']['dialogues_count']:,} dialogues ({split_stats['train']['messages_count']:,} turns)")
    print(f"Validation: {split_stats['validation']['dialogues_count']:,} dialogues ({split_stats['validation']['messages_count']:,} turns)")
    print(f"Test:       {split_stats['test']['dialogues_count']:,} dialogues ({split_stats['test']['messages_count']:,} turns)")
    print("=" * 65)

if __name__ == "__main__":
    main()
