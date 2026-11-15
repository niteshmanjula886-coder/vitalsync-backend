with open('c:/Users/nites/.android-studio/vitalsync-backend/api/reports/weekly_report.php', 'r', encoding='utf-8') as f:
    orig = f.read()

p_sec10 = orig.find('// 10. Vital Performance Breakdown')
p_sec11 = orig.find('// 11. "Your Weekly Trends" Structured Payload')
p_sec12 = orig.find('// 12. Ollama Executive Summary')

print("--- End of Sec 10 ---")
print(orig[p_sec11-200:p_sec11])

print("--- End of Sec 11 ---")
print(orig[p_sec12-200:p_sec12])

