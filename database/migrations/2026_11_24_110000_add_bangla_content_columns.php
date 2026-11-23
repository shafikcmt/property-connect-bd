<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional Bangla versions of public content. Empty means "show the English text" (see HasBangla). */
return new class extends Migration
{
    private const COLUMNS = [
        'properties' => ['title_bn' => 255, 'description_bn' => 'text'],
        'projects' => ['name_bn' => 255, 'summary_bn' => 400, 'description_bn' => 'text', 'payment_plan_bn' => 'text'],
        'developers' => ['name_bn' => 255, 'summary_bn' => 300, 'about_bn' => 'text'],
        'project_installments' => ['label_bn' => 120, 'timing_bn' => 120, 'note_bn' => 255],
        'project_updates' => ['title_bn' => 160, 'body_bn' => 'text'],
        'faqs' => ['question_bn' => 'text', 'answer_bn' => 'text'],
        'blog_posts' => ['title_bn' => 255, 'excerpt_bn' => 'text', 'body_bn' => 'text'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach ($columns as $name => $type) {
                    $type === 'text' ? $t->text($name)->nullable() : $t->string($name, $type)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(array_keys($columns)));
        }
    }
};
