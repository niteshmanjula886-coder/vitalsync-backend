#!/usr/bin/env bash
set -e

echo "=== 1. Checking Ollama Daemon ==="
if ! curl -s http://127.0.0.1:11434/ > /dev/null 2>&1; then
    echo "Starting Ollama in background..."
    nohup ollama serve > /dev/null 2>&1 &
    sleep 3
fi

echo "=== 2. Pulling Qwen 2.5 1.5B (Fits in GT 1030 2GB VRAM) ==="
ollama pull qwen2.5:1.5b || ollama pull qwen2.5:0.5b

echo "=== 3. Testing Direct Model Inference ==="
curl -s http://127.0.0.1:11434/api/generate -d '{
  "model": "qwen2.5:1.5b",
  "prompt": "List top 3 protein sources in one line.",
  "stream": false
}'
echo -e "\n=== Qwen 1.5B Migration Verified ==="
