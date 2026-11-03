SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `ai_knowledge_base` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) NOT NULL, -- 'nutrition', 'medical_basics', 'vitals_guide', 'general_wellness', 'fitness', 'lifestyle'
    `topic` VARCHAR(150) NOT NULL,
    `keywords` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT KEY `idx_ft_knowledge` (`keywords`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clear previous data if needed
TRUNCATE TABLE `ai_knowledge_base`;

-- Seed verified rich reference dataset for RAG retrieval
INSERT INTO `ai_knowledge_base` (`category`, `topic`, `keywords`, `content`) VALUES
('nutrition', 'High Protein Sources', 'protein best source chicken eggs fish whey lentils soya paneer beef vegetarian', 
'Top dietary protein sources: 
1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.
2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).
3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.'),

('nutrition', 'Micronutrients and Recovery', 'vitamins minerals omega 3 fish oil magnesium sleep recovery cramps zinc vitamin d', 
'Key recovery nutrients: Omega-3 Fatty Acids (EPA/DHA, 1000-2000mg) reduce systemic inflammation and muscle soreness. Magnesium glycinate (200-400mg) supports sleep architecture and muscle relaxation. Vitamin D3 & Zinc support hormonal balance and immune defense.'),

('medical_basics', 'Fever, Dehydration & Vitals', 'fever temp heart rate elevated sick infection dehydration fluids ors coconut water', 
'Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1°C elevation due to higher metabolic demand. If heart rate is elevated alongside low hydration, prioritize electrolyte rehydration (ORS, coconut water). Warning signs: persistent fever > 103°F (39.4°C), stiff neck, or dyspnea require immediate medical evaluation.'),

('medical_basics', 'Resting Heart Rate Metrics', 'resting heart rate bpm bradycardia tachycardia normal range athletes', 
'Clinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.'),

('medical_basics', 'Blood Pressure Stages & Guidelines', 'blood pressure hypertension hypotension systolic diastolic mmhg normal bp', 
'AHA BP Guidelines: Normal: < 120/< 80 mmHg. Elevated: 120-129/< 80 mmHg. Stage 1 Hypertension: 130-139 / 80-89 mmHg. Stage 2 Hypertension: >= 140 or >= 90 mmHg. Hypertensive Crisis: > 180 and/or > 120 mmHg. Lifestyle modifications: DASH diet, sodium reduction (< 2,300 mg/day), regular aerobic exercise, stress reduction.'),

('fitness', 'Hypertrophy and Muscle Growth Principles', 'hypertrophy muscle build progressive overload sets reps failure volume', 
'Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). Rep ranges of 6-12 reps with 2-3 minutes rest optimize mechanical tension and metabolic stress. Daily protein target: 1.6-2.2 grams per kg of total bodyweight.'),

('general_wellness', 'Sleep Architecture and Circadian Health', 'sleep stages rem deep sleep circadian rhythm melatonin insomnia', 
'Optimal sleep architecture consists of 4-6 cycles of 90 minutes each, balancing Deep NREM (physical and muscle tissue repair, glymphatic brain clearance) and REM (cognitive consolidation, mood regulation). Sleep hygiene rules: keep bedroom cool (18-20°C / 65-68°F), avoid blue light 60 mins before bed, finish eating 2-3 hours prior to sleep.'),

('nutrition', 'Hydration and Electrolyte Balance', 'hydration water intake electrolytes sodium potassium water target', 
'Hydration Physiology: Baseline daily fluid intake is roughly 30-35 ml per kg of bodyweight, increasing by 500-1000 ml per hour of intense exercise or sweating. Key intracellular and extracellular electrolytes: Sodium, Potassium, Magnesium. Sipping fluids evenly throughout the waking hours is more effective for cell hydration than chugging large amounts at once.'),

('general_wellness', 'Stress, Cortisol, and Heart Rate Variability (HRV)', 'stress cortisol hrv heart rate variability burnout breathing', 
'Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HRV, impair anabolic muscle synthesis, and disrupt sleep architecture. 5 minutes of resonance breathing (4s inhale, 6s exhale) swiftly engages parasympathetic tone.');

SET FOREIGN_KEY_CHECKS = 1;

