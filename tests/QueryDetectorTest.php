<?php

namespace BeyondCode\QueryDetector\Tests;

use BeyondCode\QueryDetector\Tests\Models\Comment;
use Route;
use BeyondCode\QueryDetector\QueryDetector;
use BeyondCode\QueryDetector\Tests\Models\Post;
use BeyondCode\QueryDetector\Tests\Models\Author;

class QueryDetectorTest extends TestCase
{
    /** @test */
    public function it_detects_n1_query_on_properties()
    {
        Route::get('/', function (){
            $authors = Author::all();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    /** @test */
    public function it_ignores_eager_loaded_relationships()
    {
        Route::get('/', function (){
            $authors = Author::with('profile')->get();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    /** @test */
    public function it_detects_n1_queries_from_builder()
    {
        Route::get('/', function (){
            $authors = Author::with('profile')->get();

            foreach ($authors as $author) {
                $author->profile;
                $author->posts()->where(1)->get();
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame(Post::class, $queries[0]['relation']);
    }

    /** @test */
    public function it_detects_all_n1_queries()
    {
        Route::get('/', function (){
            $authors = Author::with('profile')->get();

            foreach ($authors as $author) {
                $author->profile;
                $author->posts()->where(1)->get();
            }

            foreach (Post::all() as $post) {
                $post->author;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(2, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame(Post::class, $queries[0]['relation']);

        $this->assertSame(Post::count(), $queries[1]['count']);
        $this->assertSame(Post::class, $queries[1]['model']);
        $this->assertSame('author', $queries[1]['relation']);
    }

    /** @test */
    public function it_detects_n1_queries_on_morph_relations()
    {
        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Post::count(), $queries[0]['count']);
        $this->assertSame(Post::class, $queries[0]['model']);
        $this->assertSame('comments', $queries[0]['relation']);
    }

    /** @test */
    public function it_detects_n1_queries_on_morph_relations_with_builder()
    {
        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments()->get();
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);

        $this->assertSame(Post::count(), $queries[0]['count']);
        $this->assertSame(Post::class, $queries[0]['model']);
        $this->assertSame(Comment::class, $queries[0]['relation']);
    }

    /** @test */
    public function it_can_be_disabled()
    {
        $this->app['config']->set('querydetector.enabled', false);

        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments()->get();
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    /** @test */
    public function it_ignores_whitelisted_relations()
    {
        $this->app['config']->set('querydetector.enabled', true);
        $this->app['config']->set('querydetector.except', [
            Post::class => [
                Comment::class
            ]
        ]);

        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments()->get();
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    /** @test */
    public function it_ignores_whitelisted_relations_with_attributes()
    {
        $this->app['config']->set('querydetector.enabled', true);
        $this->app['config']->set('querydetector.except', [
            Post::class => [
                'comments'
            ]
        ]);

        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }
}
