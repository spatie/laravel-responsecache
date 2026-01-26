<?php

namespace App\Http\Controllers;

use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;

/**
 * Example: Attribute-based Cache Configuration
 *
 * This example demonstrates how to use PHP attributes to configure
 * response caching directly on your controller methods.
 */
class PostController extends Controller
{
    /**
     * Cache for 5 minutes with tags 'posts' and 'api'
     */
    #[Cache(lifetime: 300, tags: ['posts', 'api'])]
    public function index()
    {
        $posts = Post::latest()->paginate(20);

        return view('posts.index', compact('posts'));
    }

    /**
     * Cache for 10 minutes with a single tag
     */
    #[Cache(lifetime: 600, tags: ['posts'])]
    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    /**
     * Use flexible/SWR caching:
     * - Fresh for 3 minutes (180 seconds)
     * - Stale while revalidate for 15 minutes (900 seconds)
     * - Defer refresh to background
     */
    #[FlexibleCache(fresh: 180, stale: 900, defer: true, tags: ['posts'])]
    public function popular()
    {
        $posts = Post::orderBy('views', 'desc')->take(10)->get();

        return view('posts.popular', compact('posts'));
    }

    /**
     * Explicitly disable caching for this endpoint
     */
    #[NoCache]
    public function store(Request $request)
    {
        $post = Post::create($request->validated());

        return redirect()->route('posts.show', $post);
    }
}

/**
 * Class-level attribute applies to all methods
 */
#[Cache(lifetime: 600, tags: ['pages'])]
class PageController extends Controller
{
    public function about()
    {
        // Will be cached for 600 seconds with 'pages' tag
        return view('pages.about');
    }

    public function contact()
    {
        // Will be cached for 600 seconds with 'pages' tag
        return view('pages.contact');
    }

    /**
     * Method-level attribute overrides class-level
     */
    #[NoCache]
    public function contactSubmit(Request $request)
    {
        // Won't be cached despite class-level attribute
        // Process form submission...
        return redirect()->route('contact');
    }
}
