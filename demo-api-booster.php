<?php
/**
 * ApiBooster
 * 在特定的 API 路徑下，只載入必要的插件
 */

namespace J7\PowerCourse\Compatibility\ApiOptimize;

/**
 * ApiBooster
 */
final class ApiBooster {

	/**
	 * Namespaces 只有這幾個 namespace 的 API 請求，才會載入必要的插件
	 *
	 * @var array<string>
	 */
	protected static $namespaces = [
		'checkout',
		'wc-ajax',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		\add_action('muplugins_loaded', [ __CLASS__, 'only_load_required_plugins' ], 1);
	}

	/**
	 * Only Load Required Plugins
	 * 只載入必要的插件
	 *
	 * @return void
	 */
	public static function only_load_required_plugins(): void {

		// 檢查是否為 $namespace API 請求
		$some_strpos = false;
		foreach (self::$namespaces as $namespace) {
			if (strpos($_SERVER['REQUEST_URI'], $namespace) !== false) { // phpcs:ignore
				$some_strpos = true;
				break;
			}
		}
		if (!$some_strpos) {
			return;
		}

		// 只保留需要的插件
		$required_plugins = [
			'woocommerce/woocommerce.php',
			'woomp/woomp.php'
		];

		// 檢查是否所有必要的插件都已經載入
		// 取得所有已啟用的插件
		$active_plugins = (array) \get_option('active_plugins');
		$all_required_plugins_included = array_intersect($required_plugins, $active_plugins);
		if (count($all_required_plugins_included) !== count($required_plugins)) {
			return;
		}

				$hooks_to_remove_in_checkout = [
			'widgets_init',
			'register_sidebar',
			'wp_register_sidebar_widget',
			'admin_bar_init',
			'add_admin_bar_menus',
		];

		// 移除不必要的 WordPress 功能
		$hooks_to_remove_wc_ajax = [
			'setup_theme',
			// 'after_setup_theme', // 會錯誤
			'wp_default_scripts',
			'wp_default_styles',
			'wp_loaded',
		];

		$is_checkout = strpos($_SERVER['REQUEST_URI'], 'checkout') !== false;

		$hooks_to_remove = 	$is_checkout ? $hooks_to_remove_in_checkout : [
			...$hooks_to_remove_in_checkout,
			...$hooks_to_remove_wc_ajax,
		];

		foreach ( $hooks_to_remove as $hook ) {
			\add_action(
				$hook,
				function () use ( $hook ) {
					\remove_all_actions($hook);
				},
				-999999
				);
		}

		\add_filter('option_active_plugins', fn () => $required_plugins );
	}
}

new ApiBooster();
