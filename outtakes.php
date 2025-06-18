<?php

    if(isset($postvars['all_active_plugins'])) {
        $active_plugins = explode(',',$postvars['all_active_plugins']);
        foreach($active_plugins as $slug) {
            if(in_array($slug, $slugs)) {
                continue; // skip duplicate themes
            }
            $slugs[] = $slug; // add to slugs to avoid duplicates

            if('theme-plugin-playground' == $slug) {
                continue; // skip excluded plugins
            }
            
            $public = true;

            if(quickplayground_repo_check($slug,'plugin')) {

            if(isset($_POST['settings']) || isset($_GET['reset']))
                printf('<p>Adding public plugin %s</p>',htmlentities($slug));

            } else {
                $public = false;
            if(isset($_POST['settings']) || isset($_GET['reset']))
                printf('<p>Public plugin %s not found, adding as local zip</p>',htmlentities($slug));
                quickplayground_playground_zip_plugin($slug);
            }
            $steps[] = makePluginItem($slug, $public, true);
        }
    }

    foreach($default_plugins as $slug) {
        if(in_array($slug, $slugs)) {
            continue; // skip duplicate themes
        }
        $slugs[] = $slug; // add to slugs to avoid duplicates

        if('theme-plugin-playground' == $slug) {
            continue; // skip excluded plugins
        }
        
        $public = true;

        if(quickplayground_repo_check($slug,'plugin')) {

            if(isset($_POST['settings']) || isset($_GET['reset']))
            printf('<p>Adding public plugin %s</p>',htmlentities($slug));

        } else {
            $public = false;
            if(isset($_POST['settings']) || isset($_GET['reset']))
            printf('<p>Public plugin %s not found, adding as local zip</p>',htmlentities($slug));
            quickplayground_playground_zip_plugin($slug);
        }
        $steps[] = makePluginItem($slug, $public, true);
    }
