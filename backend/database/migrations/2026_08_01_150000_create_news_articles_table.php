<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id('article_id');
            $table->string('firestore_doc_id', 128)->nullable()->unique();
            $table->foreignId('author_id')->constrained('users', 'user_id');
            $table->foreignId('park_id')->nullable()->constrained('parks', 'park_id');
            $table->string('title', 255);
            $table->text('excerpt');
            $table->longText('body')->nullable();
            $table->string('category', 100);
            $table->string('source', 150)->default('Uganda Wildlife Authority');
            $table->string('read_time', 20)->default('3 min');
            $table->enum('theme', ['FOREST', 'WILDLIFE', 'SECURITY'])->default('FOREST');
            $table->boolean('published')->default(true);
            $table->timestamp('published_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
