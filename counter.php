<?php
/*
	Plugin Name: Counter
	Description: [counter]
	Version: 1.0.0
	Author: Alexei Isaenko
	Author URI: http://www.sh14.ru

 */

class Counter {
	public static int $shortcodes = 0;
	private static int $post_id = 0;
	private static int $views = 0;
	private static string $key = 'viewscount';

	/**
	 * Получение ключа хранящего кол-во просмотров.
	 *
	 * @return string
	 */
	public static function key() {
		return self::$key;
	}

	/**
	 * Получение кол-ва просмотров для указанного поста.
	 *
	 * @return int
	 */
	public static function views() {
		if ( empty( self::$views ) ) {
			self::$views = absint( get_post_meta( self::postId(), 'viewscount', true ) );
		}

		return self::$views;
	}

	/**
	 * Получение значения id поста.
	 * @return int
	 */
	public static function postId() {
		return self::$post_id;
	}

	/**
	 * Установка id поста в переменую.
	 * @param $id
	 */
	public static function setPostId( $id ) {
		if ( ! empty( get_post_type( $id ) ) ) {
			self::$post_id = $id;
		}
	}


	/**
	 * Добавление просмотра.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return int
	 */
	public static function store( WP_REST_Request $request ) {
		global $wpdb;
		if ( empty( $request->get_param( 'post_id' ) ) ) {
			return 0;
		}

		self::setPostId( absint( $request->get_param( 'post_id' ) ) );

		if ( empty( self::postId() ) ) {
			return 0;
		}

		// инкрементация по средствам sql для устранения перезаписи старых значений
		if ( empty( self::views() ) ) {
			$query = $wpdb->prepare( "INSERT INTO {$wpdb->prefix}postmeta (post_id,meta_key,meta_value)VALUES(%d,%s,1)", self::postId(), self::key(), self::postId(), self::key() );
		} else {
			$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}postmeta SET meta_value = meta_value +1 WHERE post_id = %d AND meta_key = %s", self::postId(), self::key() );
		}
		$wpdb->query( $query );

		if ( empty( $wpdb->last_error ) ) {

			// прибавляется 1, чтобы учесть текущий просмотр
			return self::views() + 1;
		}

		return self::views();
	}
}

/**
 * Вывод шорткода.
 *
 * @return string
 */
function views_count() {
	Counter::$shortcodes ++;
	Counter::setPostId( get_the_ID() );

	return '<div id="views_count">' . __( 'Views count', 'counter' ) . ': <span>' . Counter::views() . '</span></div>';
}

add_shortcode( 'views', 'views_count' );

/**
 * Регистрация скриптов.
 */
function register_counter_scripts() {
	wp_register_script( 'views-counter', plugin_dir_url( __FILE__ ) . 'assets/js/counter.js', [
		'jquery',
		'wp-api',
	], '1.0.0', true );
	wp_localize_script( 'views-counter', 'viewscounter', [
		'post_id' => get_the_ID(),
		'allow'   => is_single() && 'post' === get_post_type(),
	] );
}

add_action( 'wp_enqueue_scripts', 'register_counter_scripts' );

/**
 * Подключение скрипта только в том случае, если на странице указан шорткод.
 */
function enqueue_counter_scripts() {
	if ( ! empty( Counter::$shortcodes ) ) {
		wp_enqueue_script( 'views-counter' );
	}
}

add_action( 'wp_footer', 'enqueue_counter_scripts' );

/**
 * Обработка эндпоинта.
 * @param WP_REST_Request $request
 */
function add_view( WP_REST_Request $request ) {
	wp_send_json_success( [
		'views' => Counter::store( $request ),
	] );
}

/**
 * Регистрация эндпоинтов
 */
function register_routes() {
	register_rest_route( 'counter/v1', '/views/(?P<post_id>\d+)', [
		'methods'  => WP_REST_Server::CREATABLE,
		'callback' => 'add_view',
	] );
}

add_action( 'rest_api_init', 'register_routes' );
