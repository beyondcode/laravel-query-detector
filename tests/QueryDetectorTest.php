<?php

namespace BeyondCode\QueryDetector\Tests;

use Route;
use Illuminate\Support\Facades\Event;
use BeyondCode\QueryDetector\QueryDetector;
use BeyondCode\QueryDetector\Events\QueryDetected;
use BeyondCode\QueryDetector\Tests\Models\Post;
use BeyondCode\QueryDetector\Tests\Models\Author;
use BeyondCode\QueryDetector\Tests\Models\Comment;

class QueryDetectorTest extends TestCase
{
    public function test_it_detects_n1_query_on_properties()
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

    public function test_it_detects_n1_query_on_multiple_requests()
    {
        Route::get('/', function (){
            $authors = Author::get();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        // first request
        $this->get('/');
        $queries = app(QueryDetector::class)->getDetectedQueries();
        $this->assertCount(1, $queries);
        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);

        // second request
        $this->get('/');
        $queries = app(QueryDetector::class)->getDetectedQueries();
        $this->assertCount(1, $queries);
        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    public function test_it_does_not_detect_a_false_n1_query_on_multiple_requests()
    {
        Route::get('/', function (){
            $authors = Author::with("profile")->get();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        // first request
        $this->get('/');
        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());

        // second request
        $this->get('/');
        $this->assertCount(0, app(QueryDetector::class)->getDetectedQueries());
    }

    public function test_it_ignores_eager_loaded_relationships()
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

    public function test_it_detects_n1_queries_from_builder()
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

    public function test_it_detects_all_n1_queries()
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

    public function test_it_detects_n1_queries_on_morph_relations()
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

    public function test_it_detects_n1_queries_on_morph_relations_with_builder()
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

    public function test_it_can_be_disabled()
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

    public function test_it_ignores_whitelisted_relations()
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

    public function test_it_ignores_whitelisted_relations_with_attributes()
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

    public function test_it_ignores_redirects()
    {
        Route::get('/', function (){
            foreach (Post::all() as $post) {
                $post->comments;
            }
            return redirect()->to('/random');
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);
    }

    public function test_it_fires_an_event_if_detects_n1_query()
    {
        Event::fake();

        Route::get('/', function (){
            $authors = Author::all();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        Event::assertDispatched(QueryDetected::class);
    }

    public function test_it_does_not_fire_an_event_if_there_is_no_n1_query()
    {
        Event::fake();

        Route::get('/', function (){
            $authors = Author::with('profile')->get();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        Event::assertNotDispatched(QueryDetected::class);
    }
    public function test_it_uses_the_trace_line_to_detect_queries()
    {
        Route::get('/', function (){
            $authors = Author::all();
            $authors2 = Author::all();

            foreach ($authors as $author) {
                $author->profile->city;
            }

            foreach ($authors2 as $author) {
                $author->profile->city;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(2, $queries);

        $this->assertSame(Author::count(), $queries[0]['count']);
        $this->assertSame(Author::class, $queries[0]['model']);
        $this->assertSame('profile', $queries[0]['relation']);
    }

    public function test_it_empty_queries()
    {
        Route::get('/', function (){
            $authors = Author::all();

            foreach ($authors as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        $queryDetector = app(QueryDetector::class);

        $queries = $queryDetector->getDetectedQueries();
        $this->assertCount(1, $queries);

        $queryDetector->emptyQueries();
        $queries = $queryDetector->getDetectedQueries();
        $this->assertCount(0, $queries);
    }

    /** @test */
    public function it_suppresses_n1_detection_with_without_detection()
    {
        Route::get('/', function () {
            app(QueryDetector::class)->withoutDetection(function () {
                $authors = Author::all();

                foreach ($authors as $author) {
                    $author->profile;
                }
            });
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }

    /** @test */
    public function it_resumes_detection_after_without_detection()
    {
        Route::get('/', function () {
            $detector = app(QueryDetector::class);

            $detector->withoutDetection(function () {
                foreach (Author::all() as $author) {
                    $author->profile;
                }
            });

            // This should still be detected
            foreach (Post::all() as $post) {
                $post->comments;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);
        $this->assertSame(Post::class, $queries[0]['model']);
        $this->assertSame('comments', $queries[0]['relation']);
    }

    /** @test */
    public function it_returns_closure_value_from_without_detection()
    {
        $result = app(QueryDetector::class)->withoutDetection(function () {
            return 'hello';
        });

        $this->assertSame('hello', $result);
    }

    /** @test */
    public function it_resumes_detection_even_if_closure_throws()
    {
        Route::get('/', function () {
            $detector = app(QueryDetector::class);

            try {
                $detector->withoutDetection(function () {
                    throw new \RuntimeException('test');
                });
            } catch (\RuntimeException $e) {
                // expected
            }

            // Detection should be active again
            foreach (Author::all() as $author) {
                $author->profile;
            }
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(1, $queries);
    }

    /** @test */
    public function it_supports_nested_without_detection_calls()
    {
        Route::get('/', function () {
            $detector = app(QueryDetector::class);

            $detector->withoutDetection(function () use ($detector) {
                // Inner nested call
                $detector->withoutDetection(function () {
                    foreach (Author::all() as $author) {
                        $author->profile;
                    }
                });

                // After inner call returns, outer should still be suppressed
                foreach (Post::all() as $post) {
                    $post->comments;
                }
            });
        });

        $this->get('/');

        $queries = app(QueryDetector::class)->getDetectedQueries();

        $this->assertCount(0, $queries);
    }
}
