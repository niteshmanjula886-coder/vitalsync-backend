"""
VitalSync Core AI - Neural Inference Engine
Loads trained model weights, predicts user intent with confidence scores,
and generates context-aware clinical wellness responses with dynamic telemetry binding.
"""

import os
import sys
import json
import random
from model import tokenize, stem, get_active_indices, VitalSyncNeuralClassifier

MODEL_PATH = os.path.join(os.path.dirname(__file__), "vitalsync_model.json")

class VitalSyncInferenceEngine:
    def __init__(self, model_path=MODEL_PATH):
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Model file not found at: {model_path}. Run train.py first.")
            
        with open(model_path, "r", encoding="utf-8") as f:
            self.bundle = json.load(f)
            
        self.vocab = self.bundle["vocab"]
        self.classes = self.bundle["classes"]
        self.responses = self.bundle["responses"]
        net_data = self.bundle["network"]
        
        self.classifier = VitalSyncNeuralClassifier(
            net_data["input_size"],
            net_data["hidden1_size"],
            net_data["hidden2_size"],
            net_data["num_classes"]
        )
        self.classifier.import_weights(net_data)

    def predict_intent(self, text):
        tokens = tokenize(text)
        active_idx = get_active_indices(tokens, self.vocab)
        if not active_idx:
            return None, 0.0
            
        probs, _, _ = self.classifier.forward_sparse(active_idx)
        max_prob = max(probs)
        best_idx = probs.index(max_prob)
        return self.classes[best_idx], max_prob

    def generate_response(self, text, context=None, confidence_threshold=0.40):
        if context is None:
            context = {}

        # Default telemetry values
        name = context.get("name") or "there"
        if name == "User":
            name = "there"
        hr = context.get("heart_rate") or 72
        bp = context.get("blood_pressure") or "120/80 mmHg"
        spo2 = context.get("spo2") or "98%"
        steps = context.get("today_steps") or 6450
        cals = context.get("today_calories") or 380
        sleep_hours = context.get("last_sleep_hours") or "7h 30m"
        sleep_score = context.get("last_sleep_score") or 84
        water_ml = context.get("today_water_ml") or 1600
        target_water = context.get("water_target_ml") or 2500
        water_pct = round((water_ml / max(1, target_water)) * 100)
        goal = context.get("primary_goal") or "General Wellness & Cardiovascular Fitness"

        hydration_advice = (
            "You are well-hydrated today; keep sipping steadily."
            if water_ml >= target_water
            else f"Drinking 2 more glasses will help reach your full {target_water} mL target."
        )

        replacements = {
            "{name}": name,
            "{heart_rate}": str(hr),
            "{blood_pressure}": str(bp),
            "{spo2}": str(spo2),
            "{today_steps}": f"{steps:,}",
            "{today_calories}": f"{cals:,}",
            "{last_sleep_hours}": str(sleep_hours),
            "{last_sleep_score}": str(sleep_score),
            "{today_water_ml}": f"{water_ml:,}",
            "{water_target_ml}": f"{target_water:,}",
            "{water_percent}": str(water_pct),
            "{primary_goal}": goal,
            "{hydration_advice}": hydration_advice
        }

        tag, conf = self.predict_intent(text)

        if tag and conf >= confidence_threshold:
            candidates = self.responses.get(tag, [])
            template = random.choice(candidates) if candidates else "I've analyzed your telemetry."
            for k, v in replacements.items():
                template = template.replace(k, v)
                
            is_emergency = tag.startswith("clinical_emergency_")
            return {
                "success": True,
                "text": template,
                "intent": tag,
                "confidence": round(conf, 4),
                "model": "vitalsync-neural-v1",
                "is_emergency": is_emergency
            }

        # Fallback for unclassified / broad queries
        return {
            "success": True,
            "text": f"That's a thoughtful inquiry! As your VitalSync Core AI, I continuously monitor your health data (Resting HR: {hr} BPM, Activity: {steps:,} steps, Sleep: {sleep_hours}) alongside verified clinical baselines. What specific aspect of your wellness, workout recovery, or general questions can we focus on?",
            "intent": "general_knowledge_fallback",
            "confidence": round(conf, 4) if conf else 0.0,
            "model": "vitalsync-neural-v1",
            "is_emergency": False
        }

if __name__ == "__main__":
    import sys
    engine = VitalSyncInferenceEngine()
    test_queries = [
        "How is my heart rate today?",
        "Check my sleep stats",
        "I have severe chest pain radiating to left arm",
        "What is the best rep range for muscle growth?",
        "How much water did I drink?",
        "Tell me a joke"
    ]
    
    print("============================================================")
    print("[TEST] Running VitalSync Core AI Inference Tests")
    print("============================================================")
    for q in test_queries:
        res = engine.generate_response(q, context={"name": "Alex", "heart_rate": 68, "today_steps": 8420})
        clean_text = res['text'][:140].encode('ascii', errors='ignore').decode('ascii')
        print(f"\n[QUERY]: {q}")
        print(f"[INTENT]: {res['intent']} (Confidence: {res['confidence'] * 100:.1f}%)")
        print(f"[REPLY]: {clean_text}...")
