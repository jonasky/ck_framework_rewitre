<?php


namespace app\Modules\Blog;
use app\ModuleFunction;
use app\Modules\Blog\Table\PostsTable;
use ck_framework\Pagination\Pagination;
use ck_framework\Renderer\RendererInterface;
use ck_framework\Router\Router;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;



class BlogModule extends ModuleFunction
{

    CONST DEFINITIONS = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    /**
     * @var PostsTable
     */
    private $postsTable;

    /**
     * BlogModule constructor.
     * @param Router $router
     * @param RendererInterface $renderer
     * @param ContainerInterface $container
     * @param PostsTable $postsTable
     * @throws Exception
     */
    public function __construct(Router $router, RendererInterface  $renderer, ContainerInterface $container, PostsTable $postsTable){
        parent::init($router, $renderer, $container, __DIR__);
        $this->postsTable = $postsTable;
    }

    /**
     * List route for this module
     * example :
     *     $this->AddRoute(
     *          '/world', {uri}
     *          'index', {function name}
     *          'blog.index' {route name}
     *          'true' [use module prefix !true default}
     *      );
     *
     * @return void
     */
    public function ListRoute(): void {
        $this->AddRoute(
            '/',
            [$this, 'index'],
            'posts.index'
        );

        $this->AddRoute(
            '/{slug:[a-z\-0-9]+}-{id:[0-9]+}',
            [$this, 'show'],
            'posts.show'
        );
    }

    public function show(Request $request){
        //get uri Attribute
        $RequestSlug = $request->getAttribute('slug');
        $RequestId = $request->getAttribute('id');

        //try get article
        $post = $this->postsTable->FindBySlug($RequestSlug);
        if (empty($post)){$post = $this->postsTable->FindById($RequestId);}

        //get id end slug request
        $postId = $post->id;
        $postSlug = $post->slug;

        //render page or redirect if uri not clear (slug or id not complete)
        if ($postId == $RequestId && $postSlug == $RequestSlug){
            return $this->Render("show" , ['post' => $post]);
        }else{return $this->router->redirect("posts.show", ['slug' => $postSlug, 'id' => $postId]);}
    }

    public function index()
    {
        $redirect = 'posts.index';

        if (!isset($_GET['p'])) {$current = 1;} else {$current = (int)$_GET['p'];}

        $postsCount = $this->postsTable->CountAll();
        $Pagination = new Pagination(
            10,
            5,
            $postsCount[0],
            $redirect

        );

        $Pagination->setCurrentStep($current);
        $posts = $this->postsTable->FindResultLimit($Pagination->GetLimit(), $Pagination->getDbElementDisplay());

        if (empty($posts)){return $this->router->redirect($redirect, [], ['p' => 1]);}
        return $this->Render("index" ,
            [
                'posts' => $posts,
                'dataPagination' => $Pagination
            ]
        );
    }

}