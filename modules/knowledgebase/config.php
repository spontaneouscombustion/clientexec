<?php
// index=> array(permission, dependency, customer_allowed, description)
$config = array(
    'mandatory'                     => true,
    'recommended'                   => true,
    'description'                   => 'Knowledge Base module.',
    'navButtonLabel'                => lang('KnowledgeBase'),
    'dependencies'  => array(),
    'hasSchemaFile'                 => true,
    'hasInitialData'                => true,
    'hasUninstallSQLScript'         => false,
    'hasUninstallPHPScript'         => false,
    'order'                         => 5.5,
    'settingTypes'                  => array(12),
    'hooks'         => array(),
    'permissions' => array(
        1   => array('knowledgebase_view',                             0, true,   lang('View the knowledgebase')),
        2   => array('knowledgebase_viewArticle',                      1, true,   lang('View an article')),
        3   => array('knowledgebase_viewCategory',                     1, true,   lang('View a category')),
        4   => array('knowledgebase_manageArticles',                   1, false,  lang('Allow to manage the articles')),
        5   => array('knowledgebase_add_article',                      1, false,  lang('Add an article to the knowledgebase')),
        6   => array('knowledgebase_setStatus',                        1, false,  lang('Set an article\'s status')),
        7   => array('knowledgebase_manageCategories',                 1, false,  lang('Allow to manage the categories')),
        8   => array('knowledgebase_add_category',                     7, false,  lang('Add a category to the knowledgebase')),
        9   => array('knowledgebase_manageComments',                   1, false,  lang('Allow to manage the comments')),
       10   => array('knowledgebase_postComments',                     1, true,   lang('Post Comments')),
    ),
    'hreftarget' => '#'
);
?>
