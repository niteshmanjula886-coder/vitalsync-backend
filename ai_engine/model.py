"""
VitalSync Core AI - Neural Network Architecture
High-Performance Pure Python Deep Learning Neural Network for Intent Classification & Clinical Reasoning.
Optimized with sparse forward activations, Momentum SGD, and Softmax Cross-Entropy.
"""

import math
import random
import json
import re


def tokenize(text):
    """Splits text into clean lowercase word tokens."""
    cleaned = re.sub(r"[^a-zA-Z0-9\s]", " ", text.lower())
    return [w for w in cleaned.split() if len(w) > 0]


def stem(word):
    """Lightweight suffix stemming for medical & conversational vocabulary."""
    w = word.lower()
    if len(w) <= 3:
        return w
    
    suffixes = [
        ("ational", "ate"), ("tional", "tion"), ("enci", "ence"), ("anci", "ance"),
        ("izer", "ize"), ("bli", "ble"), ("alli", "al"), ("entli", "ent"),
        ("eli", "e"), ("ousli", "ous"), ("ization", "ize"), ("ation", "ate"),
        ("ator", "ate"), ("alism", "al"), ("iveness", "ive"), ("fulness", "ful"),
        ("ousness", "ous"), ("aliti", "al"), ("iviti", "ive"), ("biliti", "ble"),
        ("icate", "ic"), ("ative", ""), ("alize", "al"), ("iciti", "ic"),
        ("ical", "ic"), ("ful", ""), ("ness", ""), ("ing", ""),
        ("ies", "y"), ("es", ""), ("ed", ""), ("ly", ""), ("s", "")
    ]
    for suf, rep in suffixes:
        if w.endswith(suf) and len(w) - len(suf) >= 2:
            return w[:-len(suf)] + rep
    return w


def get_active_indices(tokenized_sentence, vocabulary):
    """Returns list of active word indices in vocabulary (sparse representation)."""
    stemmed_sentence = set([stem(w) for w in tokenized_sentence])
    return [idx for idx, w in enumerate(vocabulary) if w in stemmed_sentence]


class VitalSyncNeuralClassifier:
    """Fast 3-Layer MLP with Sparse Layer 1, ReLU activations, and Momentum SGD."""
    def __init__(self, input_size, hidden1_size, hidden2_size, num_classes):
        self.input_size = input_size
        self.hidden1_size = hidden1_size
        self.hidden2_size = hidden2_size
        self.num_classes = num_classes
        
        # He / Kaiming normal initialization
        std1 = math.sqrt(2.0 / max(1, input_size))
        std2 = math.sqrt(2.0 / max(1, hidden1_size))
        std3 = math.sqrt(2.0 / max(1, hidden2_size))
        
        self.w1 = [[random.gauss(0.0, std1) for _ in range(hidden1_size)] for _ in range(input_size)]
        self.b1 = [0.0] * hidden1_size
        
        self.w2 = [[random.gauss(0.0, std2) for _ in range(hidden2_size)] for _ in range(hidden1_size)]
        self.b2 = [0.0] * hidden2_size
        
        self.w3 = [[random.gauss(0.0, std3) for _ in range(num_classes)] for _ in range(hidden2_size)]
        self.b3 = [0.0] * num_classes
        
        # Momentum velocity matrices
        self.v_w1 = [[0.0] * hidden1_size for _ in range(input_size)]
        self.v_b1 = [0.0] * hidden1_size
        self.v_w2 = [[0.0] * hidden2_size for _ in range(hidden1_size)]
        self.v_b2 = [0.0] * hidden2_size
        self.v_w3 = [[0.0] * num_classes for _ in range(hidden2_size)]
        self.v_b3 = [0.0] * num_classes

    @staticmethod
    def softmax(z):
        max_z = max(z)
        exp_z = [math.exp(v - max_z) for v in z]
        sum_exp = sum(exp_z)
        return [v / sum_exp for v in exp_z]

    def forward_sparse(self, active_indices):
        """Sparse forward pass: evaluates Layer 1 only at active word positions."""
        # Layer 1
        a1 = list(self.b1)
        for idx in active_indices:
            row = self.w1[idx]
            for j in range(self.hidden1_size):
                a1[j] += row[j]
        # ReLU activation
        for j in range(self.hidden1_size):
            if a1[j] < 0.0:
                a1[j] = 0.0
                
        # Layer 2
        a2 = list(self.b2)
        for i in range(self.hidden1_size):
            if a1[i] > 0.0:
                row = self.w2[i]
                val = a1[i]
                for j in range(self.hidden2_size):
                    a2[j] += val * row[j]
        # ReLU activation
        for j in range(self.hidden2_size):
            if a2[j] < 0.0:
                a2[j] = 0.0
                
        # Layer 3 (Output logits)
        z3 = list(self.b3)
        for i in range(self.hidden2_size):
            if a2[i] > 0.0:
                row = self.w3[i]
                val = a2[i]
                for j in range(self.num_classes):
                    z3[j] += val * row[j]
                    
        probs = self.softmax(z3)
        return probs, a1, a2

    def train_step_sparse(self, active_indices, target_idx, lr=0.04, momentum=0.85):
        probs, a1, a2 = self.forward_sparse(active_indices)
        loss = -math.log(max(probs[target_idx], 1e-12))
        
        # Output gradient: probs - one_hot
        grad_z3 = list(probs)
        grad_z3[target_idx] -= 1.0
        
        # Backprop through Layer 3
        grad_a2 = [0.0] * self.hidden2_size
        for i in range(self.hidden2_size):
            if a2[i] > 0.0:
                sum_g = 0.0
                val_a2 = a2[i]
                for j in range(self.num_classes):
                    g = grad_z3[j]
                    sum_g += self.w3[i][j] * g
                    # Update w3 with momentum
                    v = momentum * self.v_w3[i][j] + (1.0 - momentum) * (val_a2 * g)
                    self.v_w3[i][j] = v
                    self.w3[i][j] -= lr * v
                grad_a2[i] = sum_g
        for j in range(self.num_classes):
            v = momentum * self.v_b3[j] + (1.0 - momentum) * grad_z3[j]
            self.v_b3[j] = v
            self.b3[j] -= lr * v

        # Backprop through Layer 2 (ReLU)
        grad_a1 = [0.0] * self.hidden1_size
        for i in range(self.hidden1_size):
            if a1[i] > 0.0:
                sum_g = 0.0
                val_a1 = a1[i]
                for j in range(self.hidden2_size):
                    if a2[j] > 0.0:
                        g = grad_a2[j]
                        sum_g += self.w2[i][j] * g
                        v = momentum * self.v_w2[i][j] + (1.0 - momentum) * (val_a1 * g)
                        self.v_w2[i][j] = v
                        self.w2[i][j] -= lr * v
                grad_a1[i] = sum_g
        for j in range(self.hidden2_size):
            if a2[j] > 0.0:
                v = momentum * self.v_b2[j] + (1.0 - momentum) * grad_a2[j]
                self.v_b2[j] = v
                self.b2[j] -= lr * v

        # Backprop through Layer 1 (ReLU and sparse active indices)
        for idx in active_indices:
            row_w1 = self.w1[idx]
            row_v1 = self.v_w1[idx]
            for j in range(self.hidden1_size):
                if a1[j] > 0.0:
                    g = grad_a1[j]
                    v = momentum * row_v1[j] + (1.0 - momentum) * g
                    row_v1[j] = v
                    row_w1[j] -= lr * v
        for j in range(self.hidden1_size):
            if a1[j] > 0.0:
                v = momentum * self.v_b1[j] + (1.0 - momentum) * grad_a1[j]
                self.v_b1[j] = v
                self.b1[j] -= lr * v

        return loss, probs

    def export_weights(self):
        return {
            "input_size": self.input_size,
            "hidden1_size": self.hidden1_size,
            "hidden2_size": self.hidden2_size,
            "num_classes": self.num_classes,
            "w1": self.w1,
            "b1": self.b1,
            "w2": self.w2,
            "b2": self.b2,
            "w3": self.w3,
            "b3": self.b3
        }

    def import_weights(self, data):
        self.w1 = data["w1"]
        self.b1 = data["b1"]
        self.w2 = data["w2"]
        self.b2 = data["b2"]
        self.w3 = data["w3"]
        self.b3 = data["b3"]
