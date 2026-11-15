with open('c:/Users/nites/.android-studio/vitalsync-backend/api/reports/weekly_report.php', 'r', encoding='utf-8') as f:
    orig = f.read()

p_sec5 = orig.find('// 5. Sleep Stats')
p_sec6 = orig.find('// 6. Hydration Stats')
p_sec7 = orig.find('// 7. Steps & Physical Activity')
p_sec8 = orig.find('// 8. 7-Day Daily Breakdown Series Array')

print("--- End of Sec 5 ---")
print(orig[p_sec6-300:p_sec6])

print("--- End of Sec 6 ---")
print(orig[p_sec7-300:p_sec7])

print("--- End of Sec 7 ---")
print(orig[p_sec8-300:p_sec8])

