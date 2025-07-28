<?php
// api/next_task.php

header('Content-Type: application/json');
require '../db.php'; // Assumes db.php is in the parent directory (htdocs)

$session_code = $_POST['session_code'] ?? '';
$session_file_path = '../sessions/' . $session_code . '.json';

if (empty($session_code) || !file_exists($session_file_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session.']);
    exit;
}

$session_data = json_decode(file_get_contents($session_file_path), true);

// 1. SAVE SCORES from the previous task, if they were sent.
if (isset($_POST['scores'])) {
    $scores = json_decode($_POST['scores'], true);
    if ($session_data['current_task_idx'] >= 0) {
        $scenario_name = $session_data['scenarios_to_run'][$session_data['current_scenario_idx']];
        if (!isset($session_data['scores'][$scenario_name])) {
            $session_data['scores'][$scenario_name] = [];
        }
        $session_data['scores'][$scenario_name][$session_data['current_task_idx']] = $scores;
    }
}

// 2. DEFINE ALL SCENARIO DATA (Tasks and Images)
$all_scenarios_data = [
    "Urgent Care Center" => [
        ["task" => "You’re visiting urgent care because you fell off a stool yesterday. Your right shoulder is hurting, but you did not hit your head. You need to check in at the front desk.", "image" => "images/urgent_care1.jpg"],
        ["task" => "You walk up to the front desk. Communicate what happened and fill out the sign-in sheet in front of you.", "image" => "images/urgent_care2.jpg"],
        ["task" => "The front desk worker says, “The doctor will see you in 25 minutes. Can you tell me your date of birth, and the city you live in?”", "image" => "images/urgent_care3.jpg"],
        ["task" => "The doctor calls you into the exam room. “Hello I'm Dr. Smith, what brings you here today?”...", "image" => "images/urgent_care4.jpg"],
        ["task" => "The doctor takes an X-ray of your shoulder and says the results will be ready in a few days...", "image" => "images/urgent_care5.jpg"]
    ],
    "Restaurant" => [
        ["task" => "You're going out for lunch at a busy restaurant. You'll Need to check in with the host", "image" => "images/restaurant1.jpg"],
        ["task" => "You arrive at the host stand. The host asks you to write your name and how many people are in your party on the waitlist.", "image" => "images/restaurant2.jpg"],
        ["task" => "The server greets you and says, “Hi there! My name is Lauren. What would you like to order today?”...", "image" => "images/restaurant3.jpg"],
        ["task" => "The server says, “What side would you like with that?”...", "image" => "images/restaurant4.jpg"],
        ["task" => "The server returns and says, “Oh no, I'm sorry — we're out of the dish you ordered.”...", "image" => "images/restaurant5.jpg"]
    ],
    "Grocery Store" => [
        ["task" => "You're going to the Grocery Store to shop for groceries", "image" => "images/grocery1.jpg"],
        ["task" => "Before shopping, write a list of four grocery items you need to buy...", "image" => "images/grocery2.jpg"],
        ["task" => "You cannot find two items on your list. Pick two items from your list and ask for help", "image" => "images/grocery3.jpg"],
        ["task" => "Your friend call and says you need another item from the store...", "image" => "images/grocery4.jpg"],
        ["task" => "After your items are scanned, the price seems a little too high...", "image" => "images/grocery5.jpg"]
    ],
    "Calling a Repair Service" => [
        ["task" => "It's a hot summer day and your air conditioner has stopped working. You're calling a repair service to schedule a visit.", "image" => "images/hvac1.jpg"],
        ["task" => "Find the number for the repair service and call...", "image" => "images/hvac2.jpg"],
        ["task" => "The receptionist responds, “Can you look at the air conditioning unit and describe what it looks like?”...", "image" => "images/hvac3.jpg"],
        ["task" => "The receptionist says, “We can come out Friday at 3:00pm.”...", "image" => "images/hvac4.jpg"],
        ["task" => "Before ending the call, ask two questions to gather more information about the service", "image" => "images/hvac5.jpg"]
    ]
];


// 3. ADVANCE THE ASSESSMENT STATE
if ($session_data['status'] !== 'finished') {
    $session_data['current_task_idx']++;
    $session_data['status'] = 'in_progress';

    $current_scenario_idx = $session_data['current_scenario_idx'];
    $current_scenario_name = $session_data['scenarios_to_run'][$current_scenario_idx];
    $tasks_in_current_scenario = $all_scenarios_data[$current_scenario_name];

    if ($session_data['current_task_idx'] >= count($tasks_in_current_scenario)) {
        $session_data['current_scenario_idx']++;
        $session_data['current_task_idx'] = 0;
    }

    // 4. CHECK IF ASSESSMENT IS COMPLETE
    if ($session_data['current_scenario_idx'] >= count($session_data['scenarios_to_run'])) {
        $session_data['status'] = 'finished';
        
        // --- Calculate and save final results to the database ---
        $domains = ["Language Expression", "Language Comprehension", "Fluency", "Cognition", "Multimodal Communication"];
        $raw_scores = [];
        foreach ($domains as $domain) { $raw_scores[$domain] = []; }
        $overall_scores = [];

        foreach ($session_data['scores'] as $scenario => $tasks) {
            foreach ($tasks as $task_scores) {
                foreach ($domains as $domain) { if (isset($task_scores[$domain])) $raw_scores[$domain][] = $task_scores[$domain]; }
                if (isset($task_scores['Overall'])) $overall_scores[] = $task_scores['Overall'];
            }
        }
        
        $raw_averages = [];
        foreach ($domains as $domain) { $raw_averages[$domain] = !empty($raw_scores[$domain]) ? array_sum($raw_scores[$domain]) / count($raw_scores[$domain]) : 0; }
        $overall_communication_score = !empty($overall_scores) ? array_sum($overall_scores) / count($overall_scores) : 0;
        $weighted_scores = [];
        foreach ($domains as $domain) { $weighted_scores[$domain] = $raw_averages[$domain] * ($overall_communication_score / 5.0) * 5; }
        
        $scenarios_ran_text = (count($session_data['scenarios_to_run']) >= count($all_scenarios_data)) ? "Full Assessment" : implode(", ", $session_data['scenarios_to_run']);

        $stmt = $conn->prepare("INSERT INTO assessment_results (patient_id, assessment_date, overall_score, scenarios_ran, lang_expr_raw, lang_expr_weighted, lang_comp_raw, lang_comp_weighted, fluency_raw, fluency_weighted, cognition_raw, cognition_weighted, multimodal_raw, multimodal_weighted) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $patient_id_to_save = $session_data['patient_profile']['id'];
        
        // The type string 'ids...' means: integer, double, string, double, double...
        $stmt->bind_param("idsdddddddddd", 
            $patient_id_to_save, 
            $overall_communication_score, 
            $scenarios_ran_text,
            $raw_averages['Language Expression'], $weighted_scores['Language Expression'],
            $raw_averages['Language Comprehension'], $weighted_scores['Language Comprehension'],
            $raw_averages['Fluency'], $weighted_scores['Fluency'],
            $raw_averages['Cognition'], $weighted_scores['Cognition'],
            $raw_averages['Multimodal Communication'], $weighted_scores['Multimodal Communication']
        );
        $stmt->execute();
        $stmt->close();
    }
}

// 5. ADD CURRENT TASK INFO TO THE RESPONSE
if ($session_data['status'] === 'in_progress') {
    $scenario_name = $session_data['scenarios_to_run'][$session_data['current_scenario_idx']];
    $task_index = $session_data['current_task_idx'];
    $current_task_data = $all_scenarios_data[$scenario_name][$task_index];

    $session_data['current_scenario_text'] = $scenario_name;
    $session_data['current_task_text'] = $current_task_data['task'];
    $session_data['current_image_url'] = $current_task_data['image'];
}

// 6. SAVE AND RESPOND
file_put_contents($session_file_path, json_encode($session_data, JSON_PRETTY_PRINT));
echo json_encode(['status' => 'success', 'new_state' => $session_data]);
