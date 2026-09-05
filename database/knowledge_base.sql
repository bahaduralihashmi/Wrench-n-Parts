-- ============================================
-- MECHBOT AI KNOWLEDGE BASE (RAG) TABLES
-- ============================================

-- Knowledge Base Articles (repair guides, service intervals, torque specs)
CREATE TABLE IF NOT EXISTS kb_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category ENUM('repair_guide','service_interval','torque_spec','general') NOT NULL DEFAULT 'general',
    keywords VARCHAR(255) DEFAULT '',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DTC / OBD-II Diagnostic Trouble Codes
CREATE TABLE IF NOT EXISTS kb_dtc_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    `system` VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    causes TEXT,
    fixes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Frequently Asked Questions
CREATE TABLE IF NOT EXISTS kb_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Expert Problem Diagnostics (530 problems: symptoms -> causes -> solution)
CREATE TABLE IF NOT EXISTS kb_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `system` VARCHAR(60) NOT NULL,
    problem VARCHAR(255) NOT NULL,
    symptoms TEXT,
    causes TEXT,
    solution TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`system`)
) ENGINE=InnoDB;

-- Conversation Memory (per browser session / user)
CREATE TABLE IF NOT EXISTS chatbot_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(64) NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (session_id),
    INDEX (user_id)
) ENGINE=InnoDB;

-- Multi-turn conversation state (collected vehicle info per session)
CREATE TABLE IF NOT EXISTS chatbot_state (
    session_id VARCHAR(64) NOT NULL PRIMARY KEY,
    state JSON NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Vector embeddings for semantic search
CREATE TABLE IF NOT EXISTS kb_embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('problem','article','dtc','faq') NOT NULL,
    source_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    embedding MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_src (source_type, source_id)
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA
-- ============================================

-- Repair Guides
INSERT IGNORE INTO kb_articles (title, category, keywords, content) VALUES
('Jump Starting a Dead Battery', 'repair_guide', 'jump start battery dead boost cables terminals',
 'Safety first: NEVER connect cables to battery terminals while the other car is running. Steps: 1) Park cars close but NOT touching. 2) Turn both cars off. 3) Connect RED clamp to dead battery (+) terminal, then other RED end to working battery (+). 4) Connect BLACK clamp to working battery (-), then the other BLACK end to an unpainted metal bolt on the dead car engine block (NOT the battery). 5) Start the working car, wait 2 minutes, then start the dead car. 6) Remove cables in REVERSE order. If the car does not start within 5 attempts, the battery is likely dead beyond jump starting - it may need replacement. Battery signs: slow crank, dim lights, click-click sound.'),
('How to Check Engine Oil Level', 'repair_guide', 'engine oil level dipstick check change top up',
 'Park on level ground, wait 5 minutes after turning the engine off (or check cold in the morning). 1) Pull the dipstick out and wipe it clean. 2) Reinsert fully, pull out again. 3) Oil should be between MIN and MAX marks - ideally in the upper half. 4) If below MIN: top up with the correct oil grade (check owner manual, e.g. 5W-30 or 10W-40). NEVER overfill - it damages the engine. 5) Black, gritty oil means it is due for a change. Recommended: synthetic oil every 7,500-10,000 km, conventional every 5,000 km.'),
('Replacing Windshield Wipers', 'repair_guide', 'wiper blades replacement windshield streaking squeaking',
 '1) Lift the wiper arm away from the glass. 2) Press the small tab/release button where the blade connects to the arm. 3) Slide the old blade off. 4) Slide the new blade on until it clicks. 5) Lower the arm carefully - do NOT let it snap back onto glass (it can crack the windshield). Replace wipers every 6-12 months or when they streak/squeak.'),
('How to Inspect Brake Pads', 'repair_guide', 'brake pads inspect check worn thickness squeal grinding',
 'Jack the car safely, remove the wheel. 1) Look through the caliper: brake pad material (friction layer) must be thicker than 3mm (1/4 inch). 2) If you see metal grinding the rotor, the pad is GONE - do not drive. 3) Uneven wear on one side = sticking caliper. 4) Squealing = wear indicator touching. 5) Replace pads in axle pairs (both front or both rear). Brake pads typically last 30,000-50,000 km depending on driving style.'),
('Coolant / Radiator Refill Guide', 'repair_guide', 'coolant radiator refill antifreeze overflow reservoir overheating',
 'WARNING: NEVER open the radiator cap while the engine is HOT - pressurized coolant causes severe burns. Wait until the engine is completely cold. 1) Open the overflow/reservoir tank and add premixed coolant (50/50 coolant + distilled water) up to the F mark. 2) If the reservoir is empty, check the radiator directly after the engine cools. 3) Coolant should be changed every 2-5 years or 60,000 km. 4) NEVER pour cold water on a hot engine or mix incompatible coolant colors. 5) If coolant keeps disappearing, there is a leak - check hoses, radiator, water pump, and head gasket.'),
('Spark Plug Replacement', 'repair_guide', 'spark plug replace gap change misfire hesitation fuel economy',
 '1) Engine must be COLD. 2) Remove the ignition coil or wire from the spark plug. 3) Use a spark plug socket to unscrew the old plug. 4) Check the NEW plug gap matches spec (usually 0.9-1.1mm) with a gap tool. 5) Screw in by hand first to avoid cross-threading, then torque to 20-30 Nm (15-22 ft-lb) - do NOT overtighten. 6) Replace all plugs at once. Copper plugs: every 30,000-50,000 km. Iridium/platinum: every 80,000-100,000 km. Symptoms of bad plugs: rough idle, hesitation, poor fuel economy, check engine light.'),
('Tire Pressure and Tread Check', 'repair_guide', 'tire pressure tread wear check psi inflation tyre flat',
 '1) Check pressure when tires are COLD. Correct PSI is on a sticker inside the driver door jamb (usually 30-35 PSI / 2.1-2.4 bar). 2) Use a gauge on the valve stem - do not rely on looks. 3) Tread depth test: insert a coin - if you see the coin edge fully, tread is below 1.6mm (2/32 inch) and the tire must be replaced. 4) Uneven wear = wrong pressure or bad alignment. 5) Rotate tires every 8,000-10,000 km. 6) Tires older than 6 years should be inspected yearly even if tread looks fine.'),
('Testing the Alternator', 'repair_guide', 'alternator test battery light dim charging voltage',
 'With the engine running, battery voltage should read 13.5-14.5V at the battery terminals (use a multimeter). Below 13V = alternator not charging. Signs of a failing alternator: battery warning light on, dim/flickering lights that brighten with RPM, battery dies repeatedly even after a new battery. A fully charged car battery should rest at 12.4-12.6V. If the car dies while driving and restarts after a jump only while running, suspect the alternator, not the battery.'),
('Air Filter Replacement', 'repair_guide', 'air filter replace engine air change clogged fuel economy',
 'Located in a black plastic box near the engine. 1) Unclip or unscrew the box lid. 2) Remove the old filter, note its direction. 3) Clean the box interior with a cloth. 4) Insert the new filter (check direction arrows). 5) Close and clip the lid. Replace every 15,000-30,000 km, or sooner in dusty areas. Symptoms of a clogged filter: reduced power, higher fuel consumption, rough idle, black smoke from exhaust.'),
('Timing Belt / Chain Basics', 'repair_guide', 'timing belt chain replace interval interference engine damage',
 'The timing belt synchronizes the crankshaft and camshaft. If it breaks on an INTERFERENCE engine, valves hit pistons = expensive engine damage. Replace the timing belt at 80,000-100,000 km (check owner manual - some cars need it at 60,000 km). While replacing, also replace the water pump and tensioner. Symptoms of worn belt: ticking noise from the front of the engine, slight loss of power. If your car has a timing CHAIN, it usually lasts the engine lifetime but can stretch.'),
('Clutch Problems and Signs', 'repair_guide', 'clutch slipping burning smell pedal hard soft gear shift',
 'Signs of clutch wear: 1) Engine revs but car does not accelerate = slipping. 2) Burning smell when driving = overheating clutch. 3) Pedal feels spongy or goes to floor = hydraulic issue (check clutch fluid/leaks). 4) Hard to shift gears = release bearing or clutch cable issue. A clutch usually lasts 100,000-150,000 km. If the pedal goes to the floor with no resistance, DO NOT drive - get a tow to a workshop.'),
('Brake Fluid Basics', 'repair_guide', 'brake fluid change flush level dot3 dot4 spongy pedal',
 'Brake fluid is hygroscopic - it absorbs water over time, which lowers the boiling point and causes brake fade. Replace every 2 years or 40,000 km. Check level in the reservoir (MAX mark). If fluid drops quickly, there is a leak in the system - very dangerous. Spongy brake pedal = air or water in the system (bleed the brakes). Never mix DOT 3 and DOT 5 fluids. Fluid on the ground under the car near wheels = check brake lines and calipers immediately.');

-- Service Intervals
INSERT IGNORE INTO kb_articles (title, category, keywords, content) VALUES
('Engine Oil Service Interval', 'service_interval', 'engine oil change interval km synthetic conventional 5000 7500 10000',
 'Engine oil: synthetic oil every 7,500-10,000 km or 12 months (whichever first). Conventional mineral oil: every 5,000 km or 6 months. Always replace the oil FILTER with the oil change. For heavy/dusty city driving, shorten intervals by 20-30%.'),
('Brake Pads & Rotors Service Interval', 'service_interval', 'brake pad rotor interval km replace disc 30000 50000',
 'Brake pads: every 30,000-50,000 km (front wears faster than rear). Brake rotors/discs: usually every second pad change (60,000-100,000 km) or when warped (pulsing brake pedal). Replace pads in axle pairs.'),
('Spark Plug Service Interval', 'service_interval', 'spark plug interval km copper iridium platinum 30000 50000 80000 100000',
 'Copper spark plugs: every 30,000-50,000 km. Iridium/platinum plugs: every 80,000-100,000 km. Replace all spark plugs at the same time.'),
('Air & Cabin Filter Service Interval', 'service_interval', 'air filter cabin filter interval km 15000 30000',
 'Engine air filter: every 15,000-30,000 km (10,000-15,000 km in dusty areas). Cabin (AC) filter: every 15,000-25,000 km or yearly.'),
('Coolant / Antifreeze Service Interval', 'service_interval', 'coolant antifreeze interval years km change 2 years 5 years 60000',
 'Coolant: every 2-5 years or 60,000 km. Top up with 50/50 premix of the correct type (check color: green, orange, pink, blue - do not mix types).'),
('Brake Fluid Service Interval', 'service_interval', 'brake fluid interval years km change 2 years 40000',
 'Brake fluid: every 2 years or 40,000 km. DOT 3 or DOT 4 (check cap). DOT 5 (silicone) must never be mixed with DOT 3/4.'),
('Transmission Oil Service Interval', 'service_interval', 'transmission oil gearbox interval km manual automatic 60000 80000',
 'Manual transmission oil: every 60,000 km. Automatic transmission fluid: every 80,000 km (CVT: 40,000-60,000 km). Use only the exact spec for your gearbox.'),
('Tire Rotation & Balance Service Interval', 'service_interval', 'tire rotation balance alignment interval km 10000 20000',
 'Tire rotation: every 8,000-10,000 km. Wheel alignment: every 10,000-20,000 km or when the car pulls to one side / tires wear unevenly.'),
('Timing Belt Service Interval', 'service_interval', 'timing belt interval km replace 60000 80000 100000 water pump',
 'Timing belt: every 80,000-100,000 km (some engines 60,000 km - check owner manual). Replace water pump and tensioner together. Timing chains: usually lifetime, check for noise.'),
('Power Steering Fluid Service Interval', 'service_interval', 'power steering fluid interval km change 50000',
 'Power steering fluid: check monthly, change every 50,000 km or when dark/contaminated. Use ATF or PS fluid per manual.'),
('Serpentine / Drive Belt Service Interval', 'service_interval', 'serpentine belt drive belt interval km change 80000 100000',
 'Serpentine belt: inspect every service, replace every 80,000-100,000 km or when cracked/squealing. Replace idler and tensioner if worn.'),
('Battery Service & Life', 'service_interval', 'battery life interval years replacement 3 5 test',
 'Car battery: 3-5 years typical life. Test voltage yearly after 3 years (healthy: 12.4-12.6V at rest). Clean terminals of corrosion (baking soda + water). In winter, batteries fail 2x more often.');

-- Torque Specs
INSERT IGNORE INTO kb_articles (title, category, keywords, content) VALUES
('Common Torque Specifications', 'torque_spec', 'torque nm ft-lb lug nut spark plug oil drain plug wheel bolt spec',
 'Common torque specs (always confirm with your service manual):\n- Spark plugs: 20-30 Nm (15-22 ft-lb)\n- Engine oil drain plug: 25-35 Nm (18-26 ft-lb)\n- Wheel lug nuts: 90-120 Nm (65-90 ft-lb); alloy wheels often 100-110 Nm\n- Oil filter: hand-tight + 3/4 turn (never use a wrench to install)\n- Caliper bolts: 80-100 Nm (60-75 ft-lb)\n- Strut top nuts: 30-40 Nm (22-30 ft-lb)\nOver-tightening lug nuts warps brake rotors; under-tightening is a safety hazard. Use a torque wrench in a star pattern.'),
('Engine Oil Drain Plug Torque', 'torque_spec', 'drain plug torque nm 25 30 35 over tighten strip',
 'Oil drain plug: tighten to 25-35 Nm (18-26 ft-lb). Over-tightening strips the threads in the oil pan (expensive repair). If the plug has a copper washer, replace or re-anneal it each change.'),
('Wheel Lug Nut Torque', 'torque_spec', 'lug nut torque nm wheel bolt 90 110 alloy steel star pattern',
 'Steel wheels: 90-100 Nm (65-75 ft-lb). Alloy wheels: 100-110 Nm (75-80 ft-lb). Always tighten in a STAR pattern, never clockwise around. Re-torque after 100 km of driving.');

-- DTC Codes
INSERT IGNORE INTO kb_dtc_codes (code, `system`, description, causes, fixes) VALUES
('P0300', 'Engine', 'Random / Multiple Cylinder Misfire Detected', 'Bad spark plugs or coils, vacuum leak, fuel injector clogged, low compression, intake manifold gasket leak', 'Read live misfire counters, test coils/plugs, check vacuum lines, fuel pressure and compression; replace faulty parts; clear code and test drive'),
('P0301', 'Engine', 'Cylinder 1 Misfire Detected', 'Faulty spark plug or ignition coil on cyl 1, injector problem, low compression in cyl 1', 'Swap coil with another cylinder to test (if misfire follows, coil is bad), check plug gap and fuel injector on cyl 1'),
('P0302', 'Engine', 'Cylinder 2 Misfire Detected', 'Faulty spark plug or ignition coil on cyl 2, injector problem, low compression', 'Swap coil test, inspect plug, injector pulse and compression on cylinder 2'),
('P0171', 'Fuel', 'System Too Lean (Bank 1)', 'Vacuum leak, dirty MAF sensor, weak fuel pump, clogged fuel filter, bad O2 sensor, intake leak', 'Smoke-test for vacuum leaks, clean MAF, check fuel pressure and filter, verify O2 sensor readings'),
('P0172', 'Fuel', 'System Too Rich (Bank 1)', 'Clogged air filter, faulty MAF, leaking fuel injectors, high fuel pressure, bad O2 sensor', 'Check air filter and MAF, test fuel pressure, inspect injectors, monitor fuel trims'),
('P0420', 'Exhaust', 'Catalytic Converter Efficiency Below Threshold (Bank 1)', 'Failed catalytic converter, exhaust leak before the cat, damaged O2 sensors, oil burning into exhaust', 'Check for exhaust leaks, test O2 sensor switching, may require catalytic converter replacement'),
('P0128', 'Cooling', 'Coolant Thermostat Below Regulating Temperature', 'Stuck-open thermostat, low coolant, faulty coolant temp sensor', 'Test thermostat opening temperature (typically ~88-92 C), replace if stuck open, verify coolant level'),
('P0500', 'Chassis', 'Vehicle Speed Sensor Malfunction', 'Faulty VSS, damaged wiring, broken speedometer gear', 'Check sensor resistance and wiring, test signal while rotating wheel, replace sensor'),
('P0401', 'Exhaust', 'Exhaust Gas Recirculation (EGR) Flow Insufficient', 'Carbon buildup blocking EGR passages, faulty EGR valve, clogged intake manifold', 'Clean EGR valve and passages, test valve operation, clear carbon from intake'),
('P0113', 'Engine', 'Intake Air Temperature Sensor 1 Circuit High', 'Faulty IAT sensor, open circuit/wiring, bad connection', 'Check sensor resistance vs temp, inspect wiring and connector, replace sensor'),
('P0442', 'Fuel', 'EVAP System Small Leak Detected', 'Loose or missing gas cap (most common), cracked EVAP hose, faulty vent valve', 'Tighten gas cap first and clear code, smoke-test EVAP system, test purge/vent solenoid'),
('P0455', 'Fuel', 'EVAP System Gross Leak', 'Gas cap missing/not sealing, broken EVAP hose, defective canister', 'Check gas cap, smoke test EVAP system, replace leaking components'),
('P0135', 'Engine', 'O2 Sensor Heater Circuit Malfunction (Bank 1 Sensor 1)', 'Blown heater fuse, faulty O2 sensor, wiring damage', 'Check heater fuse and relay, test sensor heater resistance (~3-10 ohms), replace sensor'),
('P0700', 'Transmission', 'Transmission Control System Malfunction', 'Internal transmission fault logged, TCM fault, wiring issue', 'Scan for stored transmission codes, check TCM wiring/connectors, may need transmission service'),
('P0101', 'Engine', 'Mass Air Flow (MAF) Circuit Range/Performance', 'Dirty or failing MAF sensor, air intake leak after MAF, clogged air filter', 'Clean MAF with spray cleaner, check for intake leaks, verify MAF readings vs airflow'),
('P0010', 'Engine', 'Camshaft Position Actuator Circuit (Bank 1)', 'Faulty VVT solenoid, oil pressure low or dirty oil, wiring issue', 'Check oil level and condition, test VVT solenoid resistance, inspect wiring'),
('P0340', 'Engine', 'Camshaft Position Sensor Circuit Malfunction', 'Faulty cam sensor, wiring damage, timing chain/belt issues', 'Check sensor and connector, verify timing, replace sensor'),
('P0446', 'Fuel', 'EVAP Vent Control Circuit Malfunction', 'Faulty vent valve/solenoid, wiring, clogged vent line', 'Test vent solenoid operation, check wiring, clear blockage'),
('P0174', 'Fuel', 'System Too Lean (Bank 2)', 'Vacuum leak on bank 2, MAF issue, fuel pressure, injector problems', 'Smoke test, check fuel trims both banks, clean MAF, check fuel pressure'),
('C0035', 'ABS', 'Left Front Wheel Speed Sensor Circuit', 'Faulty wheel speed sensor, damaged wiring, corroded connector, dirty sensor tip', 'Check sensor gap and cleanliness, test resistance, inspect harness, replace sensor');

-- FAQs
INSERT IGNORE INTO kb_faqs (question, answer, category) VALUES
('Do you provide warranty on parts?', 'Yes! Batteries come with a 2-year warranty, and most other parts include a manufacturer warranty. Keep your receipt. Warranty covers manufacturing defects, not damage from improper installation or accidents.', 'policies'),
('What is the return policy?', 'Unused parts in original packaging can be returned within 14 days of delivery. Electrical parts once installed cannot be returned. Contact support with your order number to start a return.', 'policies'),
('How long does delivery take?', 'Within the same city: 1-2 working days. Other cities: 3-5 working days. Delivery is free on orders above Rs. 5,000. You can track your order status in your customer dashboard.', 'orders'),
('Can you install the parts I buy?', 'We do not install parts, but we partner with workshops. Use our Workshop Finder to book an appointment and take the parts with you - the workshop can install them for you.', 'services'),
('How do I know which part fits my car?', 'Check the make, model, year, engine size and VIN of your vehicle, then use the product filters on our Products page. When unsure, contact us with your vehicle details and we will confirm compatibility.', 'general'),
('Which payment methods do you accept?', 'We accept Cash on Delivery (COD), bank cards, UPI and net banking. Payment is collected on delivery for COD orders.', 'orders'),
('How can I track my order?', 'Login to your customer dashboard and open Orders - you will see the current status of every order. Or ask me - tell me and I can pull up your recent orders.', 'orders'),
('Do you sell used or reconditioned parts?', 'No. All parts on Wrench n Parts are brand new and sourced from authorized suppliers. Each listing states the brand and warranty.', 'general'),
('What should I do if I receive a wrong or damaged part?', 'Do not install it. Take photos, keep the packaging, and contact support within 48 hours with your order number. We will arrange a replacement or refund.', 'policies'),
('What does the check engine light mean?', 'It can mean anything from a loose gas cap to a serious misfire. Many cars store a DTC (diagnostic code). Tell me the code (e.g. P0420) or describe the symptoms and I will help you understand it.', 'general'),
('How often should I service my car?', 'General rule: engine oil and filter every 5,000-10,000 km, air filter every 15,000-30,000 km, brake fluid every 2 years, coolant every 2-5 years, timing belt at 80,000-100,000 km. Ask me for details on any specific service.', 'general'),
('Do you offer discounts or hot deals?', 'Yes! Shopkeepers regularly list hot deals with discounts - check the Hot Deals section on the homepage and your dashboard for the latest offers.', 'general');

-- ============================================
-- EXPERT PROBLEM DATABASE (530 problems)
-- NOTE: Full seed data is in kb_problems_seed.sql
-- (run it after this file: source kb_problems_seed.sql)
-- ============================================
