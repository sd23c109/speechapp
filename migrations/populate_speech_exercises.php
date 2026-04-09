<?php
require_once '/opt/mka/bootstrap.php';

echo "Populating exercise tables with base system data...\n";

$CONSONANTS = ["B","D","F","G","H","J","K","L","M","N","P","R","S","T","V","W","Y","Z"];
$VOWELS = [
    ['code' => 'AH', 'label' => 'AH', 'type' => 'short'],
    ['code' => 'EE', 'label' => 'EE', 'type' => 'long'],
    ['code' => 'OO', 'label' => 'OO', 'type' => 'long'],
    ['code' => 'OH', 'label' => 'OH', 'type' => 'long']
];
$WORDS = [
    "apple","basket","bottle","bubble","bunny","button","cabin","camel","candy","cereal",
    "cookie","copper","cousin","cuddle","daddy","dizzy","donut","fire","flower","garden","gravy",
    "happy","jacket","jelly","jungle","jumpy","kitty","lion","little","magic","middle",
    "monkey","mommy","music","napkin","nibble","noodle","panda","pencil",
    "pickle","pillow","pizza","pocket","puddle","puppy","rainy","robot","rocket","soccer",
    "snowy","spider","sunny","tiger","ticket","tummy","turtle","wiggle","yellow","zigzag",
    "zipper"
];

try {
    $pdo->beginTransaction();
    
    // 1. Insert consonants (owner_user_uuid = NULL means system/super-user content)
    $consonantIds = [];
    $order = 0;
    foreach ($CONSONANTS as $c) {
        $stmt = $pdo->prepare("
            INSERT INTO exercise_consonants 
            (owner_user_uuid, consonant_code, consonant_label, display_order, is_active)
            VALUES (NULL, ?, ?, ?, 1)
        ");
        $stmt->execute([$c, $c, $order++]);
        $consonantIds[$c] = $pdo->lastInsertId();
        echo "Added consonant: $c (ID: {$consonantIds[$c]})\n";
    }
    
    // 2. Insert vowels
    $vowelIds = [];
    $order = 0;
    foreach ($VOWELS as $v) {
        $stmt = $pdo->prepare("
            INSERT INTO exercise_vowels 
            (owner_user_uuid, vowel_code, vowel_type, vowel_label, display_order, is_active)
            VALUES (NULL, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$v['code'], $v['type'], $v['label'], $order++]);
        $vowelIds[$v['code']] = $pdo->lastInsertId();
        echo "Added vowel: {$v['code']} (ID: {$vowelIds[$v['code']]})\n";
    }
    
    // 3. Insert CV blends (all combinations)
    $order = 0;
    foreach ($CONSONANTS as $c) {
        foreach ($VOWELS as $v) {
            $cvCode = "{$c}-{$v['code']}";
            
            $stmt = $pdo->prepare("
                INSERT INTO exercise_cv_blends 
                (owner_user_uuid, consonant_id, vowel_id, cv_code, display_order, is_active)
                VALUES (NULL, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $consonantIds[$c], 
                $vowelIds[$v['code']], 
                $cvCode, 
                $order++
            ]);
            echo "Added CV blend: $cvCode\n";
        }
    }
    
    // 4. Insert 3CV blends (all combinations)
    $order = 0;
    foreach ($CONSONANTS as $c) {
        foreach ($VOWELS as $v) {
            $blendCode = "{$c}-{$v['code']}";
            
            $stmt = $pdo->prepare("
                INSERT INTO exercise_3cv_blends 
                (owner_user_uuid, consonant_id, vowel_id, blend_code, display_order, is_active)
                VALUES (NULL, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $consonantIds[$c], 
                $vowelIds[$v['code']], 
                $blendCode, 
                $order++
            ]);
            echo "Added 3CV blend: $blendCode\n";
        }
    }
    
    // 5. Insert words
    $order = 0;
    foreach ($WORDS as $word) {
        // Simple syllable breakdown (first 2 chars + rest)
        $left = substr($word, 0, 2);
        $right = substr($word, 2);
        $breakdown = "$left-$right";
        
        $stmt = $pdo->prepare("
            INSERT INTO exercise_words 
            (owner_user_uuid, word_text, syllable_count, syllable_breakdown, display_order, is_active)
            VALUES (NULL, ?, 2, ?, ?, 1)
        ");
        $stmt->execute([$word, $breakdown, $order++]);
        echo "Added word: $word\n";
    }
    
    $pdo->commit();
    echo "\n✅ Successfully populated all exercise tables!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
