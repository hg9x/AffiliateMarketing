<?php

/**
 * Description of content
 *
 * @author Faizan Ayubi
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;

class Content extends Publisher {

    protected $rpm = array(
        "IN" => 135,
        "US" => 220,
        "CA" => 220,
        "AU" => 220,
        "GB" => 220,
        "NONE" => 100
    );

    /**
     * @before _secure, publisherLayout
     */
    public function index() {
        $this->seo(array("title" => "Favourite Categories", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        $title = RequestMethods::get("title", "");
        $category = implode(",", RequestMethods::get("category", ""));
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 12);

        $where = array(
            "title LIKE ?" => "%{$title}%",
            "category LIKE ?" => "%{$category}%",
            "live = ?" => true
        );
        
        $items = Item::all($where, array("id", "title", "image", "url", "description"), "created", "desc", $limit, $page);
        $count = Item::count($where);

        $session = Registry::get("session");

        $view->set("limit", $limit);
        $view->set("title", $title);
        $view->set("page", $page);
        $view->set("count", $count);
        $view->set("items", $items);
        $view->set("category", $category);
        $view->set("domains", $this->target());
    }
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function create() {
        $this->seo(array("title" => "Create Content", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        if (RequestMethods::post("action") == "content") {
            $item = new Item(array(
                "url" =>  RequestMethods::post("url"),
                "title" => RequestMethods::post("title"),
                "image" => $this->_upload("image", "images"),
                "commission" => 0,
                "category" => implode(",", RequestMethods::post("category", "")),
                "description" => RequestMethods::post("description"),
                "user_id" => $this->user->id
            ));
            $item->save();
            
            $rpm = new RPM(array(
                "item_id" => $item->id,
                "value" => json_encode(RequestMethods::post("rpm")),
            ));
            $rpm->save();

            $view->set("success", true);
        }
    }
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function all() {
        $this->seo(array("title" => "Manage Content", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $page = RequestMethods::get("page", 1);
        $limit = RequestMethods::get("limit", 10);
        
        $website = RequestMethods::get("website", "");
        $startdate = RequestMethods::get("startdate", date('Y-m-d', strtotime("-7 day")));
        $enddate = RequestMethods::get("enddate", date('Y-m-d', strtotime("now")));

        $where = array(
            "url LIKE ?" => "%{$website}%",
            "created >= ?" => $this->changeDate($startdate, "-1"),
            "created <= ?" => $this->changeDate($enddate, "1")
        );
        
        $contents = Item::all($where, array("id", "title", "created", "image", "url", "live"), "created", "desc", $limit, $page);
        $count = Item::count($where);

        $view->set("contents", $contents);
        $view->set("page", $page);
        $view->set("count", $count);
        $view->set("limit", $limit);
    }
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function edit($id = NULL) {
        $this->seo(array("title" => "Edit Content", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $item = Item::first(array("id = ?" => $id));
        $rpm = RPM::first(array("item_id = ?" => $item->id));
        
        if (RequestMethods::post("action") == "update") {
            $item->title = RequestMethods::post("title");
            $item->url = RequestMethods::post("url");
            $item->commission = RequestMethods::post("commission");
            $item->category = implode(",", RequestMethods::post("category"));
            $item->description = RequestMethods::post("description");
            $item->live = RequestMethods::post("live", "0");
            $item->save();

            $rpm->value = json_encode(RequestMethods::post("rpm"));
            $rpm->save();

            $view->set("success", true);
            $view->set("errors", $item->getErrors());
        }
        $view->set("item", $item);
        $view->set("categories", explode(",", $item->category));
    }
    
    /**
     * @before _secure, changeLayout, _admin
     */
    public function shortenURL() {
        $this->seo(array("title" => "Shorten URL", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        
        if (RequestMethods::get("longURL")) {
            $longURL = RequestMethods::get("longURL");
            $googl = Registry::get("googl");
            $object = $googl->shortenURL($longURL);
            
            $view->set("shortURL", $object->id);
            $view->set("googl", $object);
        }
    }

    /**
     * @before _secure, changeLayout, _admin
     */
    public function domains() {
        $this->seo(array("title" => "All Domains", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $domains = Meta::all(array("property = ?" => "domain"));

        if (RequestMethods::get("domain")) {
            $exist = Meta::first(array("property" => "domain", "value = ?" => RequestMethods::get("domain")));
            if($exist) {
                $view->set("message", "Domain Exists");
            } else {
                $domain = new Meta(array(
                    "user_id" => $this->user->id,
                    "property" => "domain",
                    "value" => RequestMethods::get("domain")
                ));
                $domain->save();
                array_push($domains, $domain);
                $view->set("message", "Domain Added Successfully");
            }
        }

        $view->set("domains", $domains);
    }

    /**
     * @before _secure, changeLayout, _admin
     */
    public function delete($id = NULL) {
        $this->noview();
        $item = Item::first(array("id = ?" => $id));

        $stats = Stat::all(array("item_id = ?" => $item->id));
        foreach ($earnings as $earning) {
            $earning->delete();
        }

        $links = Link::all(array("item_id = ?" => $item->id));
        foreach ($links as $link) {
            $stat = Stat::all(array("link_id = ?" => $link->id));
            $stat->delete();
            $link->delete();
        }

        $rpm = RPM::first(array("item_id = ?" => $item->id));
        $rpm->delete();

        $item->delete();
        self::redirect($_SERVER["HTTP_REFERER"]);        
    }

    public function resize($image, $width = 470, $height = 246) {
        $path = APP_PATH . "/public/assets/uploads/images";
        $cdn = CDN;$image = base64_decode($image);
        if ($image) {
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);

            if ($filename && $extension) {
                $thumbnail = "{$filename}-{$width}x{$height}.{$extension}";
                if (!file_exists("{$path}/{$thumbnail}")) {
                    $imagine = new \Imagine\Gd\Imagine();
                    $size = new \Imagine\Image\Box($width, $height);
                    $mode = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
                    $imagine->open("{$path}/{$image}")->thumbnail($size, $mode)->save("{$path}/resize/{$thumbnail}");
                }
                header("Location: {$cdn}uploads/images/resize/{$thumbnail}");
                exit();
            }
            header("Location: /images/{$image}");
            exit();
        } else {
            header("Location: {$cdn}img/logo.png");
            exit();
        }
    }

}
