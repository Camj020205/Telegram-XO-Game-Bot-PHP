<?php

/*
🚀 این سورس کد رو به‌صورت کاملاً رایگان از گنجینه برنامه‌نویسی بیت‌آموز دریافت کردی!  
🎯 جدیدترین سورس‌ها، آموزش‌ها و ابزارهای کاربردی رو همین الان از سایت ما دانلود کن:  
🌐 https://BitAmooz.com  

💡 دوست داری همیشه یک قدم جلوتر باشی؟  
هر روز کلی سورس رایگان، تکنیک‌های برنامه‌نویسی و نکات حرفه‌ای توی بیت‌آموز منتشر میشه!  
⏳ وقتشه که سطح کدنویسی خودتو ارتقا بدی!  
🔗 همین الان وارد سایت شو و سورس‌های بیشتری بگیر: https://BitAmooz.com  
*/

/**
 * ربات بازی XO (دوز) با قابلیت عضویت اجباری و مدیریت پیشرفته بیت آموز
 * نسخه نهایی: 3.0
 */
define('BOT_TOKEN', '7602436'); // توکن و اینجا وارد کنید
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('MANDATORY_CHANNEL', 'BitAmooz_com');

function BitBotReq($method, $parameters = [])
{
    $url = API_URL . $method;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response ? json_decode($response, true) : false;
}

function isChannelMember($user_id)
{
    $result = BitBotReq("getChatMember", [
        'chat_id' => '@' . MANDATORY_CHANNEL,
        'user_id' => $user_id
    ]);

    if ($result && isset($result['result']['status'])) {
        $allowed_statuses = ['member', 'administrator', 'creator'];
        return in_array($result['result']['status'], $allowed_statuses);
    }

    return false;
}

function checkWin($table_data)
{
    $winPatterns = [
        [0, 1, 2],
        [3, 4, 5],
        [6, 7, 8],
        [0, 3, 6],
        [1, 4, 7],
        [2, 5, 8],
        [0, 4, 8],
        [2, 4, 6]
    ];

    foreach ($winPatterns as $pattern) {
        $a = $table_data[$pattern[0]];
        $b = $table_data[$pattern[1]];
        $c = $table_data[$pattern[2]];

        if ($a != 0 && $a == $b && $b == $c) {
            return true;
        }
    }

    return false;
}

function processMessage($message)
{
    if (!isset($message['chat']['id'], $message['from']['id'])) return;

    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];

    if (isset($message['text']) && strpos($message['text'], "/start") === 0) {
        if (!isChannelMember($user_id)) {
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => "✅ عضویت در کانال", 'url' => "https://t.me/" . MANDATORY_CHANNEL]],
                    [['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"]],
                    [['text' => "🔄 بررسی عضویت", 'callback_data' => "check_membership"]]
                ]
            ];

            BitBotReq("sendMessage", [
                'chat_id' => $chat_id,
                'text' => "⚠️ برای استفاده از ربات باید در کانال زیر عضو شوید:\n" . MANDATORY_CHANNEL,
                'reply_markup' => json_encode($keyboard)
            ]);
            return;
        }

        $keyboard = [
            'inline_keyboard' => [
                [['text' => "🎮 شروع بازی جدید", 'switch_inline_query' => "xo_game"]],
                [['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"]],
                [['text' => "📢 کانال ما", 'url' => "https://t.me/" . MANDATORY_CHANNEL]]
            ]
        ];

        BitBotReq("sendMessage", [
            'chat_id' => $chat_id,
            'text' => "🎮 به ربات بازی XO خوش آمدید!\nبرای شروع بازی از دکمه زیر استفاده کنید:",
            'reply_markup' => json_encode($keyboard)
        ]);
    }
}

function inlineMessage($inline)
{
    if (!isset($inline['id'], $inline['from']['id'])) return;

    $query_id = $inline['id'];
    $user_id = $inline['from']['id'];

    if (!isChannelMember($user_id)) {
        $result = [[
            "type" => "article",
            "id" => "not_member",
            "title" => "عضویت اجباری",
            "input_message_content" => [
                "message_text" => "⚠️ برای شروع بازی باید در کانال زیر عضو شوید:\n" . MANDATORY_CHANNEL,
                "parse_mode" => "HTML"
            ],
            "reply_markup" => [
                "inline_keyboard" => [
                    [
                        ["text" => "✅ عضویت در کانال", "url" => "https://t.me/" . MANDATORY_CHANNEL],
                        ['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"],
                        ["text" => "🔄 بررسی عضویت", "callback_data" => "check_membership"]
                    ]
                ]
            ]
        ]];

        BitBotReq("answerInlineQuery", [
            "inline_query_id" => $query_id,
            "results" => json_encode($result)
        ]);
        return;
    }

    $result = [[
        "type" => "article",
        "id" => "xo_game",
        "title" => "شروع بازی XO",
        "input_message_content" => [
            "message_text" => "🎮 بازی XO 3x3\nبرای شروع بازی روی دکمه زیر کلیک کنید:",
            "parse_mode" => "HTML"
        ],
        "reply_markup" => [
            "inline_keyboard" => [
                [["text" => "▶️ شروع بازی", "callback_data" => "play_" . $user_id]]
            ]
        ]
    ]];

    BitBotReq("answerInlineQuery", [
        "inline_query_id" => $query_id,
        "results" => json_encode($result)
    ]);
}

function callbackMessage($callback)
{
    if (!isset(
        $callback['id'],
        $callback['from']['id'],
        $callback['data'],
        $callback['inline_message_id']
    )) return;

    $callback_id = $callback['id'];
    $user_id = $callback['from']['id'];
    $data = $callback['data'];
    $inline_message_id = $callback['inline_message_id'];

    BitBotReq("answerCallbackQuery", [
        'callback_query_id' => $callback_id
    ]);

    if ($data === "check_membership") {
        if (!isChannelMember($user_id)) {
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => "✅ عضویت در کانال", 'url' => "https://t.me/" . MANDATORY_CHANNEL]],
                    [['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"]],
                    [['text' => "🔄 بررسی مجدد", 'callback_data' => "check_membership"]]
                ]
            ];

            BitBotReq("editMessageText", [
                'inline_message_id' => $inline_message_id,
                'text' => "⚠️ هنوز در کانال عضو نشده‌اید!\n" . MANDATORY_CHANNEL,
                'reply_markup' => json_encode($keyboard)
            ]);
            return;
        }

        $keyboard = [
            'inline_keyboard' => [
                [['text' => "🎮 شروع بازی", 'switch_inline_query' => "xo_game"]]
            ]
        ];

        BitBotReq("editMessageText", [
            'inline_message_id' => $inline_message_id,
            'text' => "✅ عضویت شما تأیید شد! برای شروع بازی از دکمه زیر استفاده کنید:",
            'reply_markup' => json_encode($keyboard)
        ]);
        return;
    }

    if (strpos($data, "play_") === 0) {
        $creator_id = substr($data, 5);

        if (!isChannelMember($user_id) || !isChannelMember($creator_id)) {
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => "✅ عضویت در کانال", 'url' => "https://t.me/" . MANDATORY_CHANNEL]],
                    [['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"]],
                    [['text' => "🔄 بررسی عضویت", 'callback_data' => "check_membership"]]
                ]
            ];

            BitBotReq("editMessageText", [
                'inline_message_id' => $inline_message_id,
                'text' => "⚠️ هر دو بازیکن باید عضو کانال زیر باشند:\n" . MANDATORY_CHANNEL,
                'reply_markup' => json_encode($keyboard)
            ]);
            return;
        }

        if ($creator_id == $user_id) {
            BitBotReq("answerCallbackQuery", [
                'callback_query_id' => $callback_id,
                'text' => "شما سازنده بازی هستید! دوستتان باید دکمه را فشار دهد.",
                'show_alert' => true
            ]);
            return;
        }

        $table = [];
        $table_data = array_fill(0, 9, 0);

        for ($i = 0; $i < 3; $i++) {
            $row = [];
            for ($j = 0; $j < 3; $j++) {
                $index = $i * 3 + $j;
                $row[] = [
                    "text" => "◻️",
                    "callback_data" => "move_{$i}_{$j}_" . implode("_", $table_data) . "_{$creator_id}_{$user_id}_1_0"
                ];
            }
            $table[] = $row;
        }

        $table[] = [
            ["text" => "🔄 شروع مجدد", "callback_data" => "restart_{$creator_id}_{$user_id}_1"],
            ["text" => "🚫 ترک بازی", "callback_data" => "quit_{$creator_id}_{$user_id}"]
        ];

        BitBotReq("editMessageText", [
            'inline_message_id' => $inline_message_id,
            'text' => "🎮 بازی XO 3x3\n\n🔴 بازیکن 1: ❌ (شما)\n🔵 بازیکن 2: ⭕️\n\n⏱ نوبت: بازیکن 1 (❌)",
            'reply_markup' => json_encode(["inline_keyboard" => $table])
        ]);
        return;
    }

    if (strpos($data, "quit_") === 0) {
        $parts = explode('_', $data);
        if (count($parts) < 3) return;

        $player1 = $parts[1];
        $player2 = $parts[2];
        $quitter_name = $callback['from']['first_name'];

        BitBotReq("answerCallbackQuery", [
            'callback_query_id' => $callback_id,
            'text' => "شما بازی را ترک کردید!",
            'show_alert' => true
        ]);

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔄 شروع مجدد', 'callback_data' => "restart_{$player1}_{$player2}_1"]],
                [['text' => '🌐 سایت ما', 'url' => "https://bitamooz.com"]]
            ]
        ];

        BitBotReq("editMessageText", [
            'inline_message_id' => $inline_message_id,
            'text' => "❌ بازی توسط $quitter_name لغو شد!",
            'reply_markup' => json_encode($keyboard)
        ]);
        return;
    }

    if (strpos($data, "restart_") === 0) {
        $parts = explode('_', $data);
        if (count($parts) < 4) return;

        $player1 = $parts[1];
        $player2 = $parts[2];
        $last_first_player = (int)$parts[3];

        $new_first_player = $last_first_player === 1 ? 2 : 1;

        $table = [];
        $table_data = array_fill(0, 9, 0);

        for ($i = 0; $i < 3; $i++) {
            $row = [];
            for ($j = 0; $j < 3; $j++) {
                $index = $i * 3 + $j;
                $row[] = [
                    "text" => "◻️",
                    "callback_data" => "move_{$i}_{$j}_" . implode("_", $table_data) . "_{$player1}_{$player2}_{$new_first_player}_0"
                ];
            }
            $table[] = $row;
        }

        $table[] = [
            ["text" => "🔄 شروع مجدد", "callback_data" => "restart_{$player1}_{$player2}_{$new_first_player}"],
            ["text" => "🚫 ترک بازی", "callback_data" => "quit_{$player1}_{$player2}"]
        ];

        $first_player_name = $new_first_player === 1 ? "بازیکن 1 (❌)" : "بازیکن 2 (⭕️)";

        BitBotReq("editMessageText", [
            'inline_message_id' => $inline_message_id,
            'text' => "🔄 بازی مجدداً شروع شد!\n\n🎮 بازی XO 3x3\n\n🔴 بازیکن 1: ❌\n🔵 بازیکن 2: ⭕️\n\n⏱ نوبت: $first_player_name",
            'reply_markup' => json_encode(["inline_keyboard" => $table])
        ]);
        return;
    }

    if (strpos($data, "move_") === 0) {
        $parts = explode('_', $data);
        if (count($parts) < 10) return;

        $i = (int)$parts[1];
        $j = (int)$parts[2];
        $table_data = array_slice($parts, 3, 9);
        $player1 = $parts[12];
        $player2 = $parts[13];
        $current_turn = (int)$parts[14];
        $move_count = (int)$parts[15];
        $index = $i * 3 + $j;

        $current_player = ($current_turn == 1) ? $player1 : $player2;
        if ($user_id != $current_player) {
            BitBotReq("answerCallbackQuery", [
                'callback_query_id' => $callback_id,
                'text' => "⏱ نوبت شما نیست! لطفاً منتظر بمانید.",
                'show_alert' => true
            ]);
            return;
        }

        if ($table_data[$index] != 0) {
            BitBotReq("answerCallbackQuery", [
                'callback_query_id' => $callback_id,
                'text' => "❌ این خانه قبلاً انتخاب شده!",
                'show_alert' => true
            ]);
            return;
        }

        $table_data[$index] = $current_turn;
        $move_count++;
        $mark = ($current_turn == 1) ? "❌" : "⭕️";
        $next_turn = ($current_turn == 1) ? 2 : 1;

        $new_table = [];
        $n = 0;
        for ($x = 0; $x < 3; $x++) {
            $row = [];
            for ($y = 0; $y < 3; $y++) {
                $cell_value = $table_data[$n];
                $cell_text = ($cell_value == 1) ? "❌" : (($cell_value == 2) ? "⭕️" : "◻️");

                $row[] = [
                    "text" => $cell_text,
                    "callback_data" => ($cell_value == 0) ?
                        "move_{$x}_{$y}_" . implode("_", $table_data) . "_{$player1}_{$player2}_{$next_turn}_{$move_count}" :
                        "ignore"
                ];
                $n++;
            }
            $new_table[] = $row;
        }

        $new_table[] = [
            ["text" => "🔄 شروع مجدد", "callback_data" => "restart_{$player1}_{$player2}_{$current_turn}"],
            ["text" => "🚫 ترک بازی", "callback_data" => "quit_{$player1}_{$player2}"]
        ];

        $win = checkWin($table_data);
        $draw = ($move_count >= 9) && !$win;

        $message_text = "🎮 بازی XO 3x3\n\n";
        $message_text .= "🔴 بازیکن 1: ❌\n";
        $message_text .= "🔵 بازیکن 2: ⭕️\n\n";

        if ($win) {
            $winner = ($current_turn == 1) ? "1 (❌)" : "2 (⭕️)";
            $message_text .= "🏆 برنده: بازیکن $winner\n";
            $message_text .= "🎉 تبریک می‌گوییم!";
        } elseif ($draw) {
            $message_text .= "🤝 بازی مساوی شد!\n";
            $message_text .= "🔁 دوباره امتحان کنید";
        } else {
            $next_player = ($current_turn == 1) ? 2 : 1;
            $next_mark = ($next_player == 1) ? "❌" : "⭕️";
            $message_text .= "⏱ نوبت: بازیکن $next_player ($next_mark)\n";
            $message_text .= "📍 منتظر حرکت بازیکن $next_player هستیم...";
        }

        BitBotReq("editMessageText", [
            'inline_message_id' => $inline_message_id,
            'text' => $message_text,
            'reply_markup' => json_encode(["inline_keyboard" => $new_table])
        ]);
    }

    if ($data === "ignore") {
        BitBotReq("answerCallbackQuery", [
            'callback_query_id' => $callback_id,
            'text' => "⚠️ این بازی تمام شده است. برای شروع مجدد از دکمه پایین استفاده کنید.",
            'show_alert' => true
        ]);
    }
}

$content = file_get_contents("php://input");
if ($content) {
    $update = json_decode($content, true);

    if (isset($update['message'])) {
        processMessage($update['message']);
    } elseif (isset($update['inline_query'])) {
        inlineMessage($update['inline_query']);
    } elseif (isset($update['callback_query'])) {
        callbackMessage($update['callback_query']);
    }
}

/*
🚀 این سورس کد رو به‌صورت کاملاً رایگان از گنجینه برنامه‌نویسی بیت‌آموز دریافت کردی!  
🎯 جدیدترین سورس‌ها، آموزش‌ها و ابزارهای کاربردی رو همین الان از سایت ما دانلود کن:  
🌐 https://BitAmooz.com  

💡 دوست داری همیشه یک قدم جلوتر باشی؟  
هر روز کلی سورس رایگان، تکنیک‌های برنامه‌نویسی و نکات حرفه‌ای توی بیت‌آموز منتشر میشه!  
⏳ وقتشه که سطح کدنویسی خودتو ارتقا بدی!  
🔗 همین الان وارد سایت شو و سورس‌های بیشتری بگیر: https://BitAmooz.com  
*/