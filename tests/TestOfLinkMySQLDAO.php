<?php
/**
 *
 * ThinkUp/tests/TestOfLinkMySQLDAO.php
 *
 * Copyright (c) 2009-2012 Gina Trapani, Christoffer Viken
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * Test Of Link DAO
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2012 Gina Trapani, Christoffer Viken
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 * @author christoffer Viken <christoffer[at]viken[dot]me>
 */
require_once dirname(__FILE__).'/init.tests.php';
require_once THINKUP_ROOT_PATH.'webapp/_lib/extlib/simpletest/autorun.php';
require_once THINKUP_ROOT_PATH.'webapp/config.inc.php';

class TestOfLinkMySQLDAO extends ThinkUpUnitTestCase {

    public function setUp() {
        parent::setUp();
        $this->DAO = new LinkMySQLDAO();
        $this->builders = self::buildData();
    }

    protected function buildData() {
        $builders = array();
        //Insert test links (not images, not expanded)
        $counter = 0;
        while ($counter < 40) {
            $post_key = $counter + 80;
            $builders[] = FixtureBuilder::build('links', array('url'=>'http://example.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'expanded_url'=>'', 'error'=>'',
            'image_src'=>''));
            $counter++;
        }

        //Insert test links (images from Flickr, not expanded)
        $counter = 0;
        while ($counter < 5) {
            $post_key = $counter + 80;
            $builders[] = FixtureBuilder::build('links', array('url'=>'http://flic.kr/p/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'expanded_url'=>'', 'error'=>'',
            'image_src'=>'http://flic.kr/thumbnail.png'));
            $counter++;
        }

        //Insert test links with errors (images from Flickr, not expanded)
        $counter = 0;
        while ($counter < 5) {
            $post_key = $counter + 80;
            $builders[] = FixtureBuilder::build('links', array('url'=>'http://flic.kr/p/'.$counter.'e',
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key,
            'error'=>'Generic test error message, Photo not found', 'image_src'=>'http://flic.kr/thumbnail.png', 
            'expanded_url'=>'', 'error'=>''));
            $counter++;
        }

        //Insert several of the same shortened link
        $counter = 0;
        while ($counter < 5) {
            $post_key = $counter + 80;
            $builders[] = FixtureBuilder::build('links', array('url'=>'http://bit.ly/beEEfs',
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'error'=>'',  'expanded_url'=>'',
            'error'=>'', 'image_src'=>'http://iamathumbnail.png'));
            $counter++;
        }

        //Insert several posts, the last one protected.
        $counter = 0;
        while ($counter < 4) {
            $post_id = $counter + 80;
            $user_id = ($counter * 5) + 2;
            $builders[] = FixtureBuilder::build('posts', array('id'=>$post_id, 'post_id'=>$post_id,
            'network'=>'twitter', 'author_user_id'=>$user_id, 'author_username'=>'user'.$counter,
            'in_reply_to_post_id'=>0, 'is_protected' => 0, 'author_fullname'=>'User.'.$counter.' Name.'.$counter,
            'post_text'=>'Post by user'.$counter));
            $counter++;
        }
        $post_id = $counter + 80;
        $user_id = ($counter * 5) + 2;
        $builders[] = FixtureBuilder::build('posts', array('id'=>$post_id, 'post_id'=>$post_id,
        'author_user_id'=>$user_id, 'author_username'=>'user'.$counter, 'in_reply_to_post_id'=>0, 'is_protected' => 1,
        'network'=>'twitter', 'author_fullname'=>'User.'.$counter.' Name.'.$counter,
        'post_text'=>'Post by user'.$counter));
        $counter++;

        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>2, 'user_id'=>7, 'active'=>1,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>2, 'user_id'=>22, 'active'=>1,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>2, 'user_id'=>17, 'active'=>1,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>2, 'user_id'=>12, 'active'=>0,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>27, 'user_id'=>2, 'active'=>1,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>18, 'user_id'=>22, 'active'=>0,
        'network'=>'twitter'));
        $builders[] = FixtureBuilder::build('follows', array('follower_id'=>12, 'user_id'=>22,  'active'=>1,
        'network'=>'twitter'));

        return $builders;
    }

    /**
     * Destructs the database, so it can be reconstructed for next test
     */
    public function tearDown() {
        $this->builders = null;
        parent::tearDown();
        $this->DAO = null;
    }

    public function testInsert(){
        $link = new Link(array('url'=>'http://example.com/test', 'image_src'=>'',
        'expanded_url'=>'http://very.long.domain.that.nobody.would.bother.to.type.com/index.php', 
        'title'=>'Very Long URL', 'post_key'=>1234));

        $result = $this->DAO->insert($link);
        //Is insert ID returned?
        $this->assertEqual($result, 56);

        //OK now check it
        $result = $this->DAO->getLinkByUrl('http://example.com/test');
        $this->assertIsA($result, "Link");
        $this->assertEqual($result->url, 'http://example.com/test');
        $this->assertEqual($result->expanded_url,
        'http://very.long.domain.that.nobody.would.bother.to.type.com/index.php');
        $this->assertEqual($result->title, 'Very Long URL');
        $this->assertEqual($result->post_key, 1234);
        $this->assertEqual($result->image_src, '');
        $this->assertEqual($result->caption, '');
        $this->assertEqual($result->description, '');

        //test another with new fields set
        $link = new Link(array('url'=>'http://example.com/test2', 'image_src'=>'',
        'expanded_url'=>'http://very.long.domain.that.nobody.would.bother.to.type.com/index.php', 
        'title'=>'Very Long URL', 'post_key'=>1234567, 
        'image_src'=>'http://example.com/thumbnail.png', 'description'=>'My hot link', 'caption'=>"Hot, huh?"));

        $result = $this->DAO->insert($link);
        //Is insert ID returned?
        $this->assertEqual($result, 57);

        //OK now check it
        $result = $this->DAO->getLinkByUrl('http://example.com/test2');
        $this->assertIsA($result, "Link");
        $this->assertEqual($result->url, 'http://example.com/test2');
        $this->assertEqual($result->expanded_url,
        'http://very.long.domain.that.nobody.would.bother.to.type.com/index.php');
        $this->assertEqual($result->title, 'Very Long URL');
        $this->assertEqual($result->post_key, 1234567);
        $this->assertEqual($result->image_src, 'http://example.com/thumbnail.png');
        $this->assertEqual($result->caption, 'Hot, huh?');
        $this->assertEqual($result->description, 'My hot link');
    }

    /**
     * Test Of saveExpandedUrl method
     */
    public function testSaveExpandedUrl() {
        $links_to_expand = $this->DAO->getLinksToExpand();
        $this->assertIsA($links_to_expand, 'Array');
        $this->assertTrue(sizeof($links_to_expand)>0);

        //Just expanded URL
        $link = $links_to_expand[0];
        $this->DAO->saveExpandedUrl($link, "http://expandedurl.com");
        $updated_link = $this->DAO->getLinkByUrl($link);
        $this->assertEqual($updated_link->expanded_url, "http://expandedurl.com");

        //With title
        $this->DAO->saveExpandedUrl($link, "http://expandedurl1.com", 'my title');
        $updated_link = $this->DAO->getLinkByUrl($link);
        $this->assertEqual($updated_link->expanded_url, "http://expandedurl1.com");
        $this->assertEqual($updated_link->title, "my title");

        //With title and image_src
        $this->DAO->saveExpandedUrl($link, "http://expandedurl2.com", 'my title1', 'http://expandedurl2.com/thumb.png');
        $updated_link = $this->DAO->getLinkByUrl($link);
        $this->assertEqual($updated_link->expanded_url, "http://expandedurl2.com");
        $this->assertEqual($updated_link->image_src, "http://expandedurl2.com/thumb.png");
        $this->assertEqual($updated_link->title, "my title1");

        //With title, image_src, and click_count
        $this->DAO->saveExpandedUrl($link, "http://expandedurl3.com", 'my title3', '', 128);
        $updated_link = $this->DAO->getLinkByUrl($link);
        $this->assertEqual($updated_link->expanded_url, "http://expandedurl3.com");
        $this->assertEqual($updated_link->image_src, "");
        $this->assertEqual($updated_link->title, "my title3");
        $this->assertEqual($updated_link->clicks, 128);
    }

    /**
     * Test Of saveExpansionError Method
     */
    public function testSaveExpansionError() {
        $linktogeterror = $this->DAO->getLinkById(10);

        $this->assertEqual($linktogeterror->error, '');
        $this->DAO->saveExpansionError($linktogeterror->url, "This is expansion error text");

        $linkthathaserror = $this->DAO->getLinkById(10);
        $this->assertEqual($linkthathaserror->error, "This is expansion error text");
    }

    public function testUpdate(){
        $link = new Link(array('url'=>'http://example.com/test', 'image_src'=>'',
        'expanded_url'=>'http://very.long.domain.that.nobody.would.bother.to.type.com/index.php', 
        'title'=>'Very Long URL', 'post_key'=>15000));

        $result = $this->DAO->insert($link);
        $this->assertEqual($result, 56);

        $link->post_key = 15000;
        $link->title = 'Even Longer URL';
        $link->expanded_url = 'http://very.long.domain.that.nobody.would.bother.to.type.com/image.png';
        $link->description = "This is the link description";
        $link->image_src = "thumbnail.jpg";
        $link->caption = "my caption";
        $result = $this->DAO->update($link);
        $this->assertEqual($result, 1);

        //OK now check it
        $result = $this->DAO->getLinkByUrl('http://example.com/test');
        $this->assertIsA($result, "Link");
        $this->assertEqual($result->url, 'http://example.com/test');
        $this->assertEqual($result->expanded_url,
        'http://very.long.domain.that.nobody.would.bother.to.type.com/image.png');
        $this->assertEqual($result->title, 'Even Longer URL');
        $this->assertEqual($result->post_key, 15000);
        $this->assertEqual($result->id, 56);
        $this->assertEqual($result->image_src, 'thumbnail.jpg');
        $this->assertEqual($result->caption, 'my caption');
        $this->assertEqual($result->description, 'This is the link description');
    }

    public function testGetLinksByFriends(){
        $result = $this->DAO->getLinksByFriends(2, 'twitter', 15, 1, false); // not public

        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 12);
        //leep(1000);
        $posts = array(
        80=>array('pid'=>80, 'uid'=>2, 'fr'=>true),
        81=>array('pid'=>81, 'uid'=>7, 'fr'=>true),
        82=>array('pid'=>82, 'uid'=>12, 'fr'=>false),
        83=>array('pid'=>83, 'uid'=>17, 'fr'=>true),
        84=>array('pid'=>84, 'uid'=>22, 'fr'=>true)
        );
        foreach($result as $key=>$val){
            $this->assertIsA($val, "Link");
            $this->assertIsA($val->container_post, "Post");
            $num = $val->post_key;
            $pid = $posts[$num]['pid'];
            $uid = $posts[$num]['uid'];
            $this->assertEqual($val->container_post->post_id, $pid);
            $this->assertEqual($val->container_post->author_user_id, $uid);
            $this->assertEqual($val->container_post->post_text, 'Post by '.$val->container_post->author_username);
            $this->assertEqual($val->container_post->in_reply_to_post_id, 0);
            $this->assertTrue($posts[$num]['fr']);
        }
        // check pagination
        $result = $this->DAO->getLinksByFriends(2, 'twitter', 5, 2);
        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 5);
    }

    /**
     * test weeding out the protected items
     */
    public function testGetLinksByFriends2(){

        $result = $this->DAO->getLinksByFriends(2, 'twitter', 15, 1, true); // public

        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 8); // (1 protected post x 4) less than the previous test
        $posts = array(
        80=>array('pid'=>80, 'uid'=>2, 'fr'=>true),
        81=>array('pid'=>81, 'uid'=>7, 'fr'=>true),
        82=>array('pid'=>82, 'uid'=>12, 'fr'=>false),
        83=>array('pid'=>83, 'uid'=>17, 'fr'=>true),
        84=>array('pid'=>84, 'uid'=>22, 'fr'=>true)
        );
        foreach($result as $key=>$val){
            $this->assertIsA($val, "Link");
            $this->assertIsA($val->container_post, "Post");
            $num = $val->post_key;
            $pid = $posts[$num]['pid'];
            $uid = $posts[$num]['uid'];
            $this->assertEqual($val->container_post->post_id, $pid);
            $this->assertEqual($val->container_post->author_user_id, $uid);
            $this->assertEqual($val->container_post->post_text, 'Post by '.$val->container_post->author_username);
            $this->assertEqual($val->container_post->in_reply_to_post_id, 0);
            $this->assertTrue($posts[$num]['fr']);
        }
    }


    /**
     * Test Of getPhotosByFriends Method
     */
    public function testGetPhotosByFriends(){
        $result = $this->DAO->getPhotosByFriends(2, 'twitter', 15, 1, false); // not public

        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 9);
        $posts = array(
        80=>array('pid'=>80, 'uid'=>2, 'fr'=>true),
        81=>array('pid'=>81, 'uid'=>7, 'fr'=>true),
        82=>array('pid'=>82, 'uid'=>12, 'fr'=>false),
        83=>array('pid'=>83, 'uid'=>17, 'fr'=>true),
        84=>array('pid'=>84, 'uid'=>22, 'fr'=>true)
        );
        foreach($result as $key=>$val){
            $this->assertIsA($val, "Link");
            $this->assertIsA($val->container_post, "Post");
            $num = $val->post_key;
            $pid = $posts[$num]['pid'];
            $uid = $posts[$num]['uid'];
            $this->assertEqual($val->container_post->post_id, $pid);
            $this->assertEqual($val->container_post->author_user_id, $uid);
            $this->assertEqual($val->container_post->post_text, 'Post by '.$val->container_post->author_username);
            $this->assertEqual($val->container_post->in_reply_to_post_id, 0);
            $this->assertTrue($posts[$num]['fr']);
        }
        // check pagination
        $result = $this->DAO->getPhotosByFriends(2, 'twitter', 5, 2);
        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 4);
    }

    /**
     * Test Of getPhotosByFriends Method, weeding out the protected items
     */
    public function testGetPhotosByFriends2(){
        $result = $this->DAO->getPhotosByFriends(2, 'twitter', 15, 1, true); // public

        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 6); // (1 protected post x 3) less than the previous test
        $posts = array(
        80=>array('pid'=>80, 'uid'=>2, 'fr'=>true),
        81=>array('pid'=>81, 'uid'=>7, 'fr'=>true),
        82=>array('pid'=>82, 'uid'=>12, 'fr'=>false),
        83=>array('pid'=>83, 'uid'=>17, 'fr'=>true),
        84=>array('pid'=>84, 'uid'=>22, 'fr'=>true)
        );
        foreach($result as $key=>$val){
            $this->assertIsA($val, "Link");
            $this->assertIsA($val->container_post, "Post");
            $num = $val->post_key;
            $pid = $posts[$num]['pid'];
            $uid = $posts[$num]['uid'];
            $this->assertEqual($val->container_post->post_id, $pid);
            $this->assertEqual($val->container_post->author_user_id, $uid);
            $this->assertEqual($val->container_post->post_text, 'Post by '.$val->container_post->author_username);
            $this->assertEqual($val->container_post->in_reply_to_post_id, 0);
            $this->assertTrue($posts[$num]['fr']);
        }
    }

    /**
     * Test Of getLinksToExpand Method
     */
    public function testGetLinksToExpand() {
        $links_to_expand = $this->DAO->getLinksToExpand();
        $this->assertEqual(count($links_to_expand), 51);
        $this->assertIsA($links_to_expand, "array");
    }

    /**
     * Test Of getLinkByID
     */
    public function testGetLinkById() {
        $link = $this->DAO->getLinkById(1);

        $this->assertEqual($link->id, 1);
        $this->assertEqual($link->url, 'http://example.com/0');
    }

    /**
     * Test Of getLinksToExpandByURL Method
     */
    public function testGetLinksToExpandByURL() {
        $flickr_links_to_expand = $this->DAO->getLinksToExpandByUrl('http://flic.kr/');

        $this->assertEqual(count($flickr_links_to_expand), 10);
        $this->assertIsA($flickr_links_to_expand, "array");

        $flickr_links_to_expand = $this->DAO->getLinksToExpandByUrl('http://flic.kr/', 5);

        $this->assertEqual(count($flickr_links_to_expand), 5);
        $this->assertIsA($flickr_links_to_expand, "array");
    }

    /**
     * test adding a dup, with the IGNORE modifier, check the result.
     * Set counter higher to avoid clashes w/ prev inserts.
     */
    public function testUniqueConstraint1() {
        $counter = 2000;
        $pseudo_minute = str_pad($counter, 2, "0", STR_PAD_LEFT);
        $source = '<a href="http://twitter.com" rel="nofollow">Tweetie for Mac</a>';
        $q  = "INSERT IGNORE INTO tu_links (url, title, clicks, post_key, image_src) ";
        $q .= " VALUES ('http://example.com/".$counter."', 'Link $counter', 0, $counter, '');";
        $res = PDODAO::$PDO->exec($q);
        $this->assertEqual($res, 1);

        $q  = "INSERT IGNORE INTO tu_links (url, title, clicks, post_key, image_src) ";
        $q .= " VALUES ('http://example.com/".$counter."', 'Link $counter', 0, $counter, '');";
        $res = PDODAO::$PDO->exec($q);
        $this->assertEqual($res, 0);
    }

    /**
     * test adding a dup w/out the IGNORE modifier; should throw exception on second insert
     */
    public function testUniqueConstraint2() {
        $counter = 2002;
        $pseudo_minute = str_pad($counter, 2, "0", STR_PAD_LEFT);
        $source = '<a href="http://twitter.com" rel="nofollow">Tweetie for Mac</a>';
        $builder1 = $builder2 = null;
        try {
            $builder1 = FixtureBuilder::build('links', array('url'=>'http://example.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$counter, 
            'expanded_url'=>'', 'error'=>'', 'image_src'=>''));
            $builder2 = FixtureBuilder::build('links', array('url'=>'http://example.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$counter, 
            'expanded_url'=>'', 'error'=>'', 'image_src'=>''));
        } catch(PDOException $e) {
            $this->assertPattern('/Integrity constraint violation/', $e->getMessage());
        }
        $builder1 = null; $builder2 = null;
    }

    /**
     * Test of getLinksByFavorites method
     */
    public function testGetFavoritedLinks() {
        $lbuilders = array();
        // test links for fav checking
        $counter = 0;
        while ($counter < 5) {
            $post_key = $counter + 180;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $lbuilders[] = FixtureBuilder::build('links', array('url'=>'http://example2.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'expanded_url'=>'', 'error'=>'',
            'image_src'=>''));
            $counter++;
        }
        //Insert several posts for fav checking-- links will be associated with 5 of them
        $counter = 0;
        while ($counter < 10) {
            $post_id = $counter + 180;
            $user_id = ($counter * 5) + 2;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);

            $lbuilders[] = FixtureBuilder::build('posts', array('id'=>$post_id,'post_id'=>$post_id,
            'author_user_id'=>$user_id, 'author_username'=>"user$counter",
            'author_fullname'=>"User$counter Name$counter", 'author_avatar'=>'avatar.jpg',
            'post_text'=>'This is post '.$post_id, 'pub_date'=>'2009-01-01 00:'. $pseudo_minute.':00',
            'network'=>'twitter', 'in_reply_to_post_id'=>null, 'in_retweet_of_post_id'=>null, 'is_geo_encoded'=>0));

            // user '20' favorites the first 7 of the test posts, only 5 of which will have links
            if ($counter < 7) {
                $lbuilders[] = FixtureBuilder::build('favorites', array('post_id'=>$post_id,
                'author_user_id'=>$user_id, 'fav_of_user_id'=>20, 'network'=>'twitter'));
            }
            $counter++;
        }
        $result = $this->DAO->getLinksByFavorites(20, 'twitter');
        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 5);
        $lbuilders = null;
    }

    /**
     * Test of getLinksByFavorites method, weeding out the protected items
     */
    public function testGetFavoritedLinks2() {
        $lbuilders = array();
        // test links for fav checking
        $counter = 0;
        while ($counter < 5) {
            $post_key = $counter + 180;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $lbuilders[] = FixtureBuilder::build('links', array('url'=>'http://example2.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'expanded_url'=>'', 'error'=>''));
            $counter++;
        }
        //Insert several posts for fav checking-- links will be associated with 5 of them
        $counter = 0;
        while ($counter < 10) {
            $post_id = $counter + 180;
            $user_id = ($counter * 5) + 2;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $is_protected = $counter == 0 ? 1 : 0;

            $lbuilders[] = FixtureBuilder::build('posts', array('id'=>$post_id, 'post_id'=>$post_id,
            'author_user_id'=>$user_id, 'author_username'=>"user$counter",
            'author_fullname'=>"User$counter Name$counter", 'author_avatar'=>'avatar.jpg',
            'post_text'=>'This is post '.$post_id, 'pub_date'=>'2009-01-01 00:'.
            $pseudo_minute.':00', 'network'=>'twitter', 'is_protected' => $is_protected,
            'in_reply_to_post_id'=>null, 'in_retweet_of_post_id'=>null, 'is_geo_encoded'=>0));

            // user '20' favorites the first 7 of the test posts, only 5 of which will have links
            if ($counter < 7) {
                $lbuilders[] = FixtureBuilder::build('favorites', array('post_id'=>$post_id,
                'author_user_id'=>$user_id, 'fav_of_user_id'=>20, 'network'=>'twitter'));
            }
            $counter++;
        }
        $result = $this->DAO->getLinksByFavorites(20, 'twitter', 15, 1, true);
        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 4);
        $lbuilders = null;
    }

    public function testGetFavoritedLinksPaging() {
        $lbuilders = array();
        $counter = 0;
        while ($counter < 15) {
            $post_key = $counter + 280;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $lbuilders[] = FixtureBuilder::build('links', array('url'=>'http://example2.com/'.$counter,
            'title'=>'Link '.$counter, 'clicks'=>0, 'post_key'=>$post_key, 'expanded_url'=>'', 'error'=>'',
            'image_src'=>''));
            $counter++;
        }
        //create posts-- links will be associated with the first 15 of them
        $counter = 0;
        while ($counter < 30) {
            $post_id = $counter + 280;
            $user_id = ($counter * 5) + 2;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);

            $lbuilders[] = FixtureBuilder::build('posts', array('id'=>$post_id, 'post_id'=>$post_id,
            'author_user_id'=>$user_id, 'author_username'=>"user$counter",
            'author_fullname'=>"User$counter Name$counter", 'author_avatar'=>'avatar.jpg', 'network'=>'twitter',
            'post_text'=>'This is post '.$post_id, 'pub_date'=>'2009-01-01 00:'. $pseudo_minute.':00',
            'in_reply_to_post_id'=>null, 'in_retweet_of_post_id'=>null, 'is_geo_encoded'=>0));

            // user '20' favorites the first 20 of the test posts, only 15 of which will have links
            if ($counter < 20) {
                $lbuilders[] = FixtureBuilder::build('favorites', array('post_id'=>$post_id,
                'author_user_id'=>$user_id, 'fav_of_user_id'=>20, 'network'=>'twitter'));
            }
            $counter++;
        }
        // 1st page, default count is 15
        $result = $this->DAO->getLinksByFavorites(20, 'twitter');
        $this->assertIsA($result, "array");
        $this->assertEqual(count($result), 15);
        // 2nd page, ask for count of 10. So, there should be 5 favs returned.
        $result = $this->DAO->getLinksByFavorites(20, 'twitter', 10, 2);
        $this->assertEqual(count($result), 5);

        $lbuilders = null;
    }

    public function testGetLinksForPost() {
        $result = $this->DAO->getLinksForPost(80, 'twitter');
        $this->debug(Utils::varDumpToString($result));
        $this->assertEqual(4, sizeof($result)); //should be 4 links for this post
        $this->assertEqual($result[0]->url, "http://example.com/0");
        $this->assertEqual($result[1]->url, "http://flic.kr/p/0");
        $this->assertEqual($result[2]->url, "http://flic.kr/p/0e");
        $this->assertEqual($result[3]->url, "http://bit.ly/beEEfs");

        $result = $this->DAO->getLinksForPost(800, 'twitter');
        $this->assertEqual(0, sizeof($result)); //should be no links for this post
    }
}
