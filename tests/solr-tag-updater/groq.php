<?php

function groqExtractTags(string $title, string $pageHtml): array
{
    if (!GROQ_API_KEY) {
        // fallback: niciun tag dacă nu avem cheie
        return [];
    }

    $payload = json_encode([
        'model' => GROQ_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' =>
                    "You are an API that extracts standardized tags for IT job postings.\n" .
                    "Input is the FULL HTML of a job page (including menus, footer, etc.).\n" .
                    "Ignore layout and navigation and focus only on the actual job content (title, responsibilities, requirements, benefits).\n" .
                    "Your ONLY output must be valid JSON with this schema:\n" .
                    "{ \"tags\": [\"tag1\", \"tag2\", ...] }\n" .
                    "Rules for tags:\n" .
                    "- lowercase\n" .
                    "- no diacritics\n" .
                    "- no duplicates\n" .
                    "- concise single-word or short-phrase tags\n" .
                    "- maximum " . MAX_TAGS . " tags\n"
            ],
            [
                'role' => 'user',
                'content' =>
                    "Job title: {$title}\n\n" .
                    "Job page HTML:\n{$pageHtml}\n\n" .
                    "Return ONLY JSON, without explanations."
            ]
        ],
        'temperature' => 0.2,
        'max_tokens' => 512,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization' => 'Bearer ' . GROQ_API_KEY,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        fwrite(STDERR, 'Groq error: ' . curl_error($ch) . PHP_EOL);
        curl_close($ch);
        return [];
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (!isset($data['choices'][0]['message']['content'])) {
        return [];
    }

    $content = trim($data['choices'][0]['message']['content']);

    // În unele cazuri LLM poate pune ```json ... ``` – le curățăm
    $content = preg_replace('/^```json\s*/i', '', $content);
    $content = preg_replace('/```$/', '', $content);

    $json = json_decode($content, true);
    if (!is_array($json) || !isset($json['tags']) || !is_array($json['tags'])) {
        return [];
    }

    $tags = [];
    foreach ($json['tags'] as $tag) {
        if (!is_string($tag)) {
            continue;
        }
        $t = strtolower(trim($tag));
        if ($t === '') {
            continue;
        }
        $tags[] = $t;
    }

    $tags = array_values(array_unique($tags));
    if (count($tags) > MAX_TAGS) {
        $tags = array_slice($tags, 0, MAX_TAGS);
    }

    return $tags;
}
