<?php
$url = "https://www.amazon.com/dp/B01MTB55WH";

function request($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    $headers = array();
    $headers[] = "Origin: https://www.amazon.com";
    $headers[] = "Accept-Encoding: gzip, deflate, br";
    $headers[] = "Accept-Language: en-US,en;q=0.9";
    $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
    $headers[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
    $headers[] = "Accept: */*";
    $headers[] = "X-Requested-With: XMLHttpRequest";
    $headers[] = "Connection: keep-alive";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $source = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);
    return $source;
}

function content_match($name, $title_data, $data){
    
    for ($i = 0; $i < $title_data->length; $i++){
        if (strpos($title_data[$i]->textContent, $name))
            return $data[$i]->textContent;
    }
    return "";
}

function get_str($str){
    return trim(str_replace("\n", "", $str));
}

function Scrapping_Unit($url){
        $source = request($url);
        $DOM = new DOMDocument;
        libxml_use_internal_errors(true);
        if (!$DOM->loadHTML('<?xml encoding="utf-8" ?>' . $source)){
            $errors="";
            foreach (libxml_get_errors() as $error)
                $errors.=$error->message."<br/>";
            
            libxml_clear_errors();
            print "libxml errors:<br>$errors";
            return;
        }

        $xpath = new DOMXPath($DOM);

        $g = $xpath->query("//div[@id='g']");
        if ($g->length>0) {
            echo "This page is not exist";
            return;
        }
    /*1*/     $product_type    = get_str($xpath->query("//a[@id='bylineInfo']")[0]->textContent);
    /*2*/     $goods_title     = get_str($xpath->query("//span[@id='productTitle']")[0]->textContent);
    /*3*/     $goods_star      = get_str($xpath->query("//span[@id='acrPopover']")[0]->textContent);
    /*4*/     $goods_price     = get_str($xpath->query("//span[@id='priceblock_ourprice']")[0]->textContent);
    /*5*/     $merchant_info   = get_str($xpath->query("//div[@id='merchant-info']")[0]->textContent);

    /*6-11*/  $feature_bullets = $xpath->query("//div[@id='feature-bullets']/ul/li/span");
              $bullets = "";  
                if ($feature_bullets->length>0){
                    for ($i = 1; $i<$feature_bullets->length; $i++){
                        $bullets = $bullets . "<p>" . get_str($feature_bullets[$i]->textContent) . "</p>";
                    }
                }   

    /*12-18*/ $side_images     = $xpath->query("//*[@id='altImages']/ul/li/span/span/span/span/span/img");
              $images = "";
                if ($side_images->length>0){
                    for ($i = 0 ; $i < $side_images->length ; $i++){
                        $images = $images . "<p>" . $side_images[$i]->getAttribute('src') . "</p>";
                    }
                }    

            $product_detail_content = $xpath->query("//*[@id='productDetails_detailBullets_sections1']/tr/td");
            $product_detail_title = $xpath->query("//*[@id='productDetails_detailBullets_sections1']/tr/th");
                    
    /*19*/   $product_dimention = get_str(content_match('Product Dimensions', $product_detail_title, $product_detail_content));
    /*20*/   $item_weight       = get_str(content_match('Item Weight', $product_detail_title, $product_detail_content));
    /*21*/   $shipping_weight   = get_str(content_match('Shipping Weight', $product_detail_title, $product_detail_content));
    /*22*/   $manufacturer      = get_str(content_match('Manufacturer', $product_detail_title, $product_detail_content));
    /*23*/   $ASIN              = get_str(content_match('ASIN', $product_detail_title, $product_detail_content));

             $best_seller_rank = $xpath->query("//*[@id='productDetails_detailBullets_sections1']/tr/td/span/span");
             $best_seller = "";
 /*24, 25*/  for ($i = 0 ; $i < $best_seller_rank->length ; $i++){
                $best_seller = $best_seller . "<p>" . get_str($best_seller_rank[$i]->textContent) . "</p>";
             }

        echo "Product Type : ".$product_type. "<br>";
        echo "Goods Title : ".$goods_title. "<br>";
        echo "Goods Star : ".$goods_star. "<br>";
        echo "Goods Price : ".$goods_price. "<br>";
        echo "Merchant Info : ".$merchant_info. "<br>";
        echo "Bullets : ".$bullets. "<br>";
        echo "Side Images : ".$images. "<br>";
        echo "Product Dimention : ".$product_dimention. "<br>";
        echo "Item Weight : ".$item_weight. "<br>";
        echo "Shipping Weight : ".$shipping_weight. "<br>";
        echo "Manufacturer : ".$manufacturer. "<br>";
        echo "ASIN : ".$ASIN. "<br>";
        echo "Best Seller Rank : ".$best_seller. "<br>";

    $line = array(
        $product_type, $goods_title, $goods_star, $goods_price, $merchant_info,
        $bullets, $images, $product_dimention, $item_weight, $shipping_weight,
        $manufacturer, $ASIN, $best_seller
    );    

    if (file_exists("output.csv")){
        $fp = fopen('output.csv', 'a');
        $out_to_csv = array (
            $line,
        );            
    }
    else {
        $fp = fopen('output.csv', 'a');
        $out_to_csv = array (
            array(  "Type", "Title", "Stars", "Price", "Merchant", "Bullet", 
                    "Side_image", "Dimention", "Item_weight", "Shipping_weight", 
                    "Manufactuer", "ASIN", "Best_seller"
                ),
            $line,
        );    
    }
    foreach ($x as $fields) fputcsv($fp, $fields);
    fclose($fp);
}

Scrapping_Unit($url);
?>

