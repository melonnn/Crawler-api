<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Crawler_Info;
use Illuminate\Http\Request;

use HeadlessChromium\BrowserFactory;


class CrawlerController extends Controller
{
    private $crawlerInfo;

    public function __construct()
    {
        $this->crawlerInfo = new Crawler_Info();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $infos = $this->crawlerInfo::orderBy('created_at', 'DESC')->get()->toarray();

        foreach ($infos as $key => $value) {
            $data[] =   [
                'id'            =>  $value['id'],
                'title'         =>  $value['title'],
                'description'   =>  $value['description'],
                'url'           =>  urlencode($value['url']),
                'image'         =>  urlencode(env('APP_URL').'/images/'.$value['img_name']),
                'created_at'    =>  date('Y/m/d H:i:s', strtotime($value['created_at']))
            ];            
        }

        return response()->json($data, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $url = $request->url;
        $result = false;

        $pageDescription = NULL;
        $pageTitle = NULL;
        $pageBody = NULL;

        if (!empty($url)) {
            $result = true;

            $info = $this->crawlerInfo::latest('id')->first();
            
            if (!empty($info)) {
                $crawlerInfoId = $info->id+1;
            } else {
                $crawlerInfoId = 1;
            }

            $browserFactory = new BrowserFactory();

            // starts headless chrome
            $browser = $browserFactory->createBrowser();

            try {
                // creates a new page and navigate to an URL
                $page = $browser->createPage();
                $page->navigate($url)->waitForNavigation();

                $pageTitle = $page->evaluate('document.title')->getReturnValue();
                $metaDescription = $page->evaluate('document.head.querySelector("meta[name=description]")')->getReturnValue();
                if (!is_null($metaDescription)) {
                    $pageDescription = $page->evaluate('document.head.querySelector("meta[name=description]").content')->getReturnValue();
                }

                $imgName = $crawlerInfoId.'.png';

                $page->screenshot()->saveToFile('images/'.$imgName);

            } finally {
                $browser->close();
            }

            $data = [
                'url'           =>  $url,
                'title'         =>  $pageTitle,
                'description'   =>  $pageDescription,
                'img_name'      =>  $imgName
            ];

            $this->crawlerInfo::create($data);

        }

        $res = [
            'result'    =>  (int)$result
        ];
        
        if ($result) {
            return response()->json($res, 200);
        } else {
            return response()->json($res, 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $info = $this->crawlerInfo::where('id', '=', $id)->first()->toArray();
        
        $browserFactory = new BrowserFactory();

            // starts headless chrome
            $browser = $browserFactory->createBrowser();

            try {
                // creates a new page and navigate to an URL
                $page = $browser->createPage();
                $page->navigate($info['url'])->waitForNavigation();

                $pageBody = $page->evaluate('document.documentElement.innerHTML')->getReturnValue();

            } finally {
                $browser->close();
            }

        $data =   [
            'id'            =>  $info['id'],
            'title'         =>  $info['title'],
            'description'   =>  $info['description'],
            'body'          =>  $pageBody,
            'url'           =>  urlencode($info['url']),
            'image'         =>  urlencode(env('APP_URL').'/images/'.$info['img_name']),
            'created_at'    =>  date('Y/m/d H:i:s', strtotime($info['created_at']))
        ];

        return response()->json($data, 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
