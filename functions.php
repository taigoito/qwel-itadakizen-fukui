<?php
/*
Author: Taigo Ito
Author URI: https://qwel.design/
*/

// Setup

function itadakizen_setup()
{
  // アイキャッチ画像をサポート
  add_theme_support('post-thumbnails');

  // HTML5マークアップの使用
  add_theme_support('html5', [
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
  ]);

  // タイトルタグ出力
  add_theme_support('title-tag');

  // カスタムヘッダー
  add_theme_support('custom-header');

  // カスタムロゴ
  add_theme_support('custom-logo');
  
  // カスタムメニュー
  register_nav_menus([
    'primary' => 'Primary Menu'
  ]);

  // 固定ページの抜粋
  add_post_type_support('page', 'excerpt');

  // メディアサイズ指定
  update_option('thumbnail_size_w', 240);
  update_option('thumbnail_size_h', 240);
  update_option('medium_size_w', 360);
  update_option('medium_size_h', 360);
  update_option('medium_large_size_w', 0);
  update_option('medium_large_size_h', 0);
  update_option('large_size_w', 720);
  update_option('large_size_h', 720);
}
add_action('after_setup_theme', 'itadakizen_setup');


// Widgets

function itadakizen_widgets_init()
{
  register_sidebar([
    'name' => 'Blog Sidebar',
    'id' => 'blog-sidebar',
    'before_widget' => '<aside class="widget">',
    'after_widget' => '</aside>',
    'before_title' => '<h2 class="widget__title">',
    'after_title' => '</h2>'
  ]);
}
add_action('widgets_init', 'itadakizen_widgets_init');


// Scripts

function itadakizen_scripts()
{
  wp_enqueue_style('fonts', 'https://fonts.googleapis.com/css?family=Noto+Sans+JP:400|Noto+Serif+JP:500&display=swap', [], null); 
  wp_enqueue_style('style', get_template_directory_uri() . '/style.css', [], null);
}
add_action('wp_enqueue_scripts', 'itadakizen_scripts');


// Post
// デフォルト投稿タイプ呼称・アイコン変更

$post_name = '記事';
$post_icon = 'dashicons-star-filled';

function change_menulabel()
{
  global $menu;
  global $submenu;
  global $post_name;
  $menu[5][0] = $post_name;
  $submenu['edit.php'][5][0] = $post_name . '一覧';
}
add_action('admin_menu', 'change_menulabel');

function change_objectlabel()
{
  global $wp_post_types;
  global $post_name;
  global $post_icon;
  $wp_post_types['post']->label = $post_name;
  $wp_post_types['post']->labels->name = $post_name;
  $wp_post_types['post']->labels->singular_name = $post_name;
  $wp_post_types['post']->menu_icon = $post_icon;
}
add_action('init', 'change_objectlabel');


// Breadcrumb

function insert_breadcrumb()
{
  echo '<div class="breadcrumb"><ul class="breadcrumb__items">';
  echo '<li class="breadcrumb__item">' .
    '<a href="' . home_url('/') . '">top</a>' .
    '</li>';

  if (is_home()) {
    // メインページ
    echo '<li class="breadcrumb__item">' . get_post_type_object('post')->label . '一覧' . '</li>';
  } else {
    // WPオブジェクト取得
    $wp_obj = get_queried_object();

    if (is_single()) {
      // 個別投稿ページ
      $postID = $wp_obj->ID;
      $post_type = $wp_obj->post_type;
      $post_title = $wp_obj->post_title;

      // カスタム投稿タイプを判定
      global $works_slug;
      global $works_cat_slug;
      if ($post_type == 'post') {
        // 「記事」の場合、「カテゴリー」を取得
        $the_tax = 'category';
      } else if ($post_type == $works_slug) {
        // 「作品」の場合、「品名」を取得
        $the_tax = $works_cat_slug;
      } else {
        $the_tax = '';
      }

      // 投稿タイプ一覧を表示
      echo '<li class="breadcrumb__item">' .
      '<a href="' . get_post_type_archive_link($post_type) . '">' .
      get_post_type_object($post_type)->label . '一覧' .
      '</a>' .
      '</li>';

    // タクソノミーが紐づいていれば表示
    if ($the_tax != "") {
      $terms = get_the_terms($postID, $the_tax); // 投稿に紐づく全タームを取得

      if (!empty($terms)) {
        $term = $terms[0];

        // 親タームがあれば表示
        if ($term->parent > 0) {
          // 親タームのIDリストを取得
          $parent_array = array_reverse(get_ancestors($term->term_id, $the_tax));
          foreach ($parent_array as $parent_id) {
            $parent_term = get_term($parent_id, $the_tax);
            echo '<li class="breadcrumb__item">' .
                '<a href="' . get_term_link($parent_id, $the_tax) . '">' .
                $parent_term->name .
                '</a>' .
                '</li>';
            }
          }

          // 最下層タームを表示
          echo '<li class="breadcrumb__item">' .
            '<a href="' . get_term_link($term->term_id, $the_tax) . '">' .
            $term->name .
            '</a>' .
            '</li>';
        }
      }

      // 自身
      echo '<li class="breadcrumb__item">' . $post_title . '</li>';
    } else if (is_page()) {
      // 固定ページ
      $page_id = $wp_obj->ID;
      $page_title = $wp_obj->post_title;

      if ($wp_obj->post_parent > 0) {
        // 親ページ
        $parent_array = array_reverse(get_post_ancestors($page_id));
        foreach ($parent_array as $parent_id) {
          echo '<li class="breadcrumb__item">' .
            '<a href="' . get_permalink($parent_id) . '">' .
            get_the_title($parent_id) .
            '</a>' .
            '</li>';
        }
      }
      // 自身
      echo '<li class="breadcrumb__item">' . $page_title . '</li>';
    } else if (is_post_type_archive()) {
      // カスタム投稿アーカイブ
      echo '<li class="breadcrumb__item">' . $wp_obj->label . '一覧</li>';
    } else if (is_date()) {
      // 日付別
      $year = get_query_var('year');
      $month = get_query_var('monthnum');
      $day = get_query_var('day');

      if ($day > 0) {
        // 日別アーカイブ
        echo '<li class="breadcrumb__item"><a href="' . get_year_link($year) . '">' . $year . '年</a></li>' .
          '<li class="breadcrumb__item"><a href="' . get_month_link($year, $month) . '">' . $month . '月</a></li>' .
          '<li class="breadcrumb__item">' . $day . '日</li>';
      } else if ($month > 0) {
        // 月別アーカイブ
        echo '<li class="breadcrumb__item"><a href="' . get_year_link($year) . '">' . $year . '年</a></li>' .
          '<li class="breadcrumb__item">' . $month . '月</li>';
      } else {
        // 年別アーカイブ
        echo '<li class="breadcrumb__item">' . $year . '年</li>';
      }
    } else if (is_author()) {
      // 投稿者アーカイブ
      echo '<li class="breadcrumb__item">' . $wp_obj->display_name . ' の記事</li>';
    } else if (is_archive()) {
      // タームアーカイブ
      $term_id = $wp_obj->term_id;
      $term_name = $wp_obj->name;
      $tax_name = $wp_obj->taxonomy;

      // 「カテゴリー」、「タグ」の場合、「記事一覧」を表示
      if ($tax_name == 'category' || $tax_name == 'tag') {
        $post_type = 'post';
      }

      // 投稿タイプ一覧を表示
      echo '<li class="breadcrumb__item">' .
        '<a href="' . get_post_type_archive_link($post_type) . '">' .
        get_post_type_object($post_type)->label . '一覧' .
        '</a>';

      // 親ページがあれば順番に表示
      if ($wp_obj->parent > 0) {
        $parent_array = array_reverse(get_ancestors($term_id, $tax_name));
        foreach ($parent_array as $parent_id) {
          $parent_term = get_term($parent_id, $tax_name);
          echo '<li class="breadcrumb__item">' .
            '<a href="' . get_term_link($parent_id, $tax_name) . '">' .
            $parent_term->name .
            '</a>' .
            '</li>';
        }
      }
      // ターム自身の表示
      echo '<li class="breadcrumb__item">' . $term_name . '</li>';
    } else if (is_search()) {
      // 検索結果ページ
      echo '<li class="breadcrumb__item">「' . get_search_query() . '」で検索した結果</li>';
    } else if (is_404()) {
      // 404ページ
      echo '<li class="breadcrumb__item">404 Not Found</li>';
    }
  }

  echo '</ul></div>';
}


// Pagination

function insert_pagination()
{
  if (is_single()) {
    // 個別投稿ページの場合、前後の記事へ移動できる
    echo '<div class="pagination"><ul class="pagination__items">';

    // 前の記事があれば、前の記事へを表示
    $prev_post = get_previous_post();
    if (!empty($prev_post)) {
      echo '<li class="pagination__item--prev"><a href="' . get_permalink($prev_post->ID) . '"><span data-icon="ei-chevron-left"></span></a></li>';
    }

    // 次の記事があれば、次の記事へを表示
    $next_post = get_next_post();
    if (!empty($next_post)) {
      echo '<li class="pagination__item--next"><a href="' . get_permalink($next_post->ID) . '"><span data-icon="ei-chevron-right"></span></a></li>';
    }

    echo '</ul></div>';
  } else if (is_home() || is_archive() || is_search()) {
    // アーカイブページの場合、ページの切り替えができる
    global $wp_query;
    $pages = $wp_query->max_num_pages;
    $paged = get_query_var('paged') ?: 1;

    // ページ数が2ページ以上の場合から表示
    if ($pages > 1) {
      echo '<div class="pagination"><ul class="pagination__items">';

      // 最初へ
      if ($paged > 3) {
        echo '<li class="pagination__item"><a href="', get_pagenum_link(1), '">1</a></li>';
        if ($paged > 4) {
          echo '<li class="pagination__item--joint"><span>…</span></li>';
        }
      }
      // 前後へ
      for ($i = 1; $i <= $pages; $i++) {
        if ($i <= $paged + 2 && $i >= $paged - 2) {
          if ($paged === $i) {
            echo '<li class="pagination__item--active"><span>' . $i . '</span></li>';
          } else {
            echo '<li class="pagination__item"><a href="', get_pagenum_link($i), '">' . $i . '</a></li>';
          }
        }
      }
      // 最後へ
      if ($paged + 2 < $pages) {
        if ($paged  + 3 < $pages) {
          echo '<li class="pagination__item--joint"><span>…</span></li>';
        }
        echo '<li class="pagination__item"><a href="', get_pagenum_link($pages), '">' . $pages . '</a></li>';
      }

      echo '</ul></div>';
    }
  }
}


// Get my title

function get_my_title()
{
  // WPオブジェクト取得
  $wp_obj = get_queried_object();

  // デフォルト投稿タイプは全て '最新情報 - News' を返す
  if (is_home() || is_singular('post') || is_post_type_archive('post') || 
    is_category() || is_tag() || is_date() || is_author() || is_search()) {
    return '最新情報 - News';
  }
  
  if (is_single() || is_page()) {
    // 個別投稿ページ・固定ページ
    return $wp_obj->post_title;
  } else if (is_post_type_archive()) {
    // カスタム投稿アーカイブ
    return $wp_obj->label . '一覧';
  } else if (is_date()) {
    // 日付別
    $year = get_query_var('year');
    $month = get_query_var('monthnum');
    $day = get_query_var('day');
    if ($day > 0) return $year . '年' . $month . '月' . $day . '日の記事';
    else if ($month > 0) return $year . '年' . $month . '月の記事';
    else return $year . '年の記事';
  } else if (is_author()) {
    // 投稿者アーカイブ
    return $wp_obj->display_name . ' の記事';
  } else if (is_archive()) {
    // タームアーカイブ
    $term_name = $wp_obj->name;
    return $term_name;
  } else if (is_search()) {
    // 検索結果ページ
    return '「' . get_search_query() . '」で検索した結果';
  } else if (is_404()) {
    // 404ページ
    return '404 Not Found';
  }
}


// Get my slug

function get_my_slug()
{
  // WPオブジェクト取得
  $wp_obj = get_queried_object();

  if (is_single()) {
    // 個別投稿ページ
    return $wp_obj->post_type;
  } else if (!is_home() && is_page()) {
    // 固定ページ
    return $wp_obj->post_name;
  } else if (is_post_type_archive()) {
    // カスタム投稿アーカイブ
    return 'archive-' . $wp_obj->name;
  } else if (is_home() || is_date() || is_author() || is_search() || is_404()) {
    // 投稿アーカイブ
    return 'archive';
  } else if (is_archive()) {
    // タームアーカイブ
    $tax_name = $wp_obj->taxonomy;
    // 「カテゴリー」、「タグ」の場合
    if ($tax_name == 'category' || $tax_name == 'tag') {
      return 'archive';
    }
  }
}

// Body ID, Main ID
// body要素, main要素にIDを指定し、スタイル・スクリプトを適宜対応させる
// フロントページのスラグは "index" を指定すること

function body_id()
{
  echo 'id="' . get_my_slug() . '"';
}


// No image
// アイキャッチ画像の代替

function no_image($size = 'sm')
{
  echo '<img src="' . get_template_directory_uri() . '/images/no-image' . ($size === 'sm' ? '-sm' : '') . '.gif">';
}


// Excerpt
// 抜粋文字数指定

function register_excerpt_length()
{
  return 64;
}
add_filter('excerpt_length', 'register_excerpt_length', 999);


// Copyright

$copyright = date('Y') . ' Itadakizen';

function copyright()
{
  global $copyright;
  echo '&copy; ' . $copyright;
}
