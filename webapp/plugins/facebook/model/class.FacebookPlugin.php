<?php
class FacebookPlugin implements CrawlerPlugin, WebappPlugin {
    public function crawl() {
        global $db; //TODO Remove when PDO port is complete
        global $conn;

        $logger = Logger::getInstance();
        $config = Config::getInstance();
        $id = DAOFactory::getDAO('InstanceDAO');
        $oid = new OwnerInstanceDAO($db, $logger);

        //crawl Facebook user profiles
        $instances = $id->getAllActiveInstancesStalestFirstByNetwork('facebook');
        foreach ($instances as $instance) {
            $logger->setUsername($instance->network_username);
            $tokens = $oid->getOAuthTokens($instance->id);
            $session_key = $tokens['oauth_access_token'];

            $fb = new Facebook($config->getValue('facebook_api_key'), $config->getValue('facebook_api_secret'));

            $id->updateLastRun($instance->id);
            $crawler = new FacebookCrawler($instance, $fb);
            $crawler->fetchInstanceUserInfo($instance->network_user_id, $session_key);
            $crawler->fetchUserPostsAndReplies($instance->network_user_id, $session_key);

            $id->save($crawler->instance, $crawler->owner_object->post_count, $logger, $fb);
        }

        //crawl Facebook pages
        $instances = $id->getAllActiveInstancesStalestFirstByNetwork('facebook page');
        foreach ($instances as $instance) {
            $logger->setUsername($instance->network_username);
            $tokens = $oid->getOAuthTokens($instance->id);
            $session_key = $tokens['oauth_access_token'];

            $fb = new Facebook($config->getValue('facebook_api_key'), $config->getValue('facebook_api_secret'));

            $id->updateLastRun($instance->id);
            $crawler = new FacebookCrawler($instance, $fb);

            $crawler->fetchPagePostsAndReplies($instance->network_user_id, $instance->network_viewer_id, $session_key);
            $id->save($crawler->instance, 0, $logger, $fb);

        }
        $logger->close(); # Close logging

    }

    public function renderConfiguration($owner) {
        $controller = new FacebookPluginConfigurationController($owner);
        return $controller->go();
    }

    public function getChildTabsUnderPosts($instance) {
        $pd = DAOFactory::getDAO('PostDAO');

        $fb_data_tpl = Utils::getPluginViewDirectory('facebook').'facebook.inline.view.tpl';

        $child_tabs = array();

        //All tab
        $alltab = new WebappTab("all_facebook_posts", "All", '', $fb_data_tpl);
        $alltabds = new WebappTabDataset("all_facebook_posts", $pd, "getAllPosts", array($instance->network_user_id, 15, false));
        $alltab->addDataset($alltabds);
        array_push($child_tabs, $alltab);
        return $child_tabs;
    }

    public function getChildTabsUnderReplies($instance) {
        $pd = DAOFactory::getDAO('PostDAO');

        $fb_data_tpl = Utils::getPluginViewDirectory('facebook').'facebook.inline.view.tpl';
        $child_tabs = array();

        //All Replies
        $artab = new WebappTab("all_facebook_replies", "Replies", "Replies to your Facebook posts", $fb_data_tpl);
        $artabds = new WebappTabDataset("all_facebook_replies", $pd, "getAllReplies", array($instance->network_user_id, 15));
        $artab->addDataset($artabds);
        array_push($child_tabs, $artab);
        return $child_tabs;
    }

    public function getChildTabsUnderFriends($instance) {
        $fd = DAOFactory::getDAO('FollowDAO');

        $fb_data_tpl = Utils::getPluginViewDirectory('facebook').'facebook.inline.view.tpl';
        $child_tabs = array();

        //Popular friends
        $poptab = new WebappTab("friends_mostactive", 'Popular', '', $fb_data_tpl);
        $poptabds = new WebappTabDataset("facebook_users", $fd, "getMostFollowedFollowees", array($instance->network_user_id, 15));
        $poptab->addDataset($poptabds);
        array_push($child_tabs, $poptab);

        return $child_tabs;
    }

    public function getChildTabsUnderFollowers($instance) {
        $fd = DAOFactory::getDAO('FollowDAO');

        $fb_data_tpl = Utils::getPluginViewDirectory('facebook').'facebook.inline.view.tpl';
        $child_tabs = array();

        //Most followed
        $mftab = new WebappTab("followers_mostfollowed", 'Most-followed', 'Followers with most followers', $fb_data_tpl);
        $mftabds = new WebappTabDataset("facebook_users", $fd, "getMostFollowedFollowers", array($instance->network_user_id, 15));
        $mftab->addDataset($mftabds);
        array_push($child_tabs, $mftab);

        return $child_tabs;
    }

    public function getChildTabsUnderLinks($instance) {
        global $ld;

        $fb_data_tpl = Utils::getPluginViewDirectory('facebook').'facebook.inline.view.tpl';
        $child_tabs = array();

        //Links from friends
        $fltab = new WebappTab("links_from_friends", 'Links', 'Links posted on your wall', $fb_data_tpl);
        $fltabds = new WebappTabDataset("links_from_friends", $ld, "getLinksByFriends", array($instance->network_user_id));
        $fltab->addDataset($fltabds);
        array_push($child_tabs, $fltab);

        return $child_tabs;
    }
}
