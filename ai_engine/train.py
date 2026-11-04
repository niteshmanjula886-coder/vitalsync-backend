"""
VitalSync Core AI - Neural Network Training Pipeline
Trains the custom VitalSync Deep Neural Network directly from scratch.
"""

import os
import json
import random
import time
from model import tokenize, stem, get_active_indices, VitalSyncNeuralClassifier

DATASET_PATH = os.path.join(os.path.dirname(__file__), "dataset.json")
OUTPUT_MODEL_PATH = os.path.join(os.path.dirname(__file__), "vitalsync_model.json")

def load_and_preprocess_data():
    with open(DATASET_PATH, "r", encoding="utf-8") as f:
        data = json.load(f)

    all_words = []
    classes = []
    documents = []
    responses_by_tag = {}

    for intent in data["intents"]:
        tag = intent["tag"]
        classes.append(tag)
        responses_by_tag[tag] = intent["responses"]
        for pattern in intent["patterns"]:
            tokens = tokenize(pattern)
            all_words.extend(tokens)
            documents.append((tokens, tag))

    ignore_words = ["?", "!", ".", ",", "a", "an", "the"]
    stemmed_words = sorted(list(set([stem(w) for w in all_words if w not in ignore_words])))
    classes = sorted(list(set(classes)))

    print(f"[Dataset] {len(documents)} training patterns across {len(classes)} intent classes.")
    print(f"[Vocabulary] {len(stemmed_words)} unique stemmed features.")

    # Convert patterns into sparse active indices
    training_data = []
    for tokens, tag in documents:
        active_idx = get_active_indices(tokens, stemmed_words)
        target_class_idx = classes.index(tag)
        training_data.append((active_idx, target_class_idx))

    return stemmed_words, classes, training_data, responses_by_tag

def train_model(epochs=180, lr=0.035):
    print("============================================================")
    print("[TRAIN] Starting VitalSync Core AI Neural Training Pipeline")
    print("============================================================")
    
    start_time = time.time()
    vocab, classes, training_data, responses_by_tag = load_and_preprocess_data()
    
    input_size = len(vocab)
    hidden1_size = 64
    hidden2_size = 32
    num_classes = len(classes)
    
    model = VitalSyncNeuralClassifier(input_size, hidden1_size, hidden2_size, num_classes)
    
    indices = list(range(len(training_data)))
    for epoch in range(1, epochs + 1):
        random.shuffle(indices)
        total_loss = 0.0
        correct = 0
        
        for i in indices:
            active_idx, target_idx = training_data[i]
            loss, probs = model.train_step_sparse(active_idx, target_idx, lr=lr)
            total_loss += loss
            pred_idx = probs.index(max(probs))
            if pred_idx == target_idx:
                correct += 1
                
        avg_loss = total_loss / len(training_data)
        accuracy = (correct / len(training_data)) * 100.0
        
        if epoch == 1 or epoch % 20 == 0 or epoch == epochs:
            print(f"Epoch [{epoch:03d}/{epochs:03d}]  Loss: {avg_loss:.4f}  Accuracy: {accuracy:6.2f}%")
            
    train_duration = time.time() - start_time
    print(f"\n[OK] Training completed in {train_duration:.2f} seconds!")
    print(f"[METRICS] Final Training Accuracy: {accuracy:.2f}% | Final Loss: {avg_loss:.4f}")
    
    model_bundle = {
        "model_name": "VitalSync-Core-AI-v1",
        "trained_at": time.strftime("%Y-%m-%d %H:%M:%S"),
        "vocab": vocab,
        "classes": classes,
        "responses": responses_by_tag,
        "metrics": {
            "epochs": epochs,
            "final_loss": round(avg_loss, 4),
            "final_accuracy": round(accuracy, 2),
            "patterns_count": len(training_data),
            "classes_count": num_classes,
            "vocab_size": input_size
        },
        "network": model.export_weights()
    }
    
    with open(OUTPUT_MODEL_PATH, "w", encoding="utf-8") as f:
        json.dump(model_bundle, f, indent=2)
        
    print(f"[SAVED] Model weights successfully saved to: {OUTPUT_MODEL_PATH}")
    return model_bundle

if __name__ == "__main__":
    train_model()
