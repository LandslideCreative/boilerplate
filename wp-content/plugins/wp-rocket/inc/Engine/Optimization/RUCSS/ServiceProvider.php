<?php
declare( strict_types=1 );

namespace WP_Rocket\Engine\Optimization\RUCSS;

use WP_Rocket\Dependencies\League\Container\Argument\Literal\StringArgument;
use WP_Rocket\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WP_Rocket\Engine\Optimization\RUCSS\Admin\{Database, OptionSubscriber, Settings};
use WP_Rocket\Engine\Optimization\RUCSS\Admin\Subscriber as AdminSubscriber;
use WP_Rocket\Engine\Optimization\RUCSS\Context\RUCSSContext;
use WP_Rocket\Engine\Optimization\RUCSS\Context\RUCSSOptimizeContext;
use WP_Rocket\Engine\Optimization\RUCSS\Controller\Filesystem;
use WP_Rocket\Engine\Common\JobManager\Queue\Queue;
use WP_Rocket\Engine\Optimization\RUCSS\Controller\UsedCSS as UsedCSSController;
use WP_Rocket\Engine\Optimization\RUCSS\Database\Queries\UsedCSS as UsedCSSQuery;
use WP_Rocket\Engine\Optimization\RUCSS\Database\Tables\UsedCSS as UsedCSSTable;
use WP_Rocket\Engine\Optimization\RUCSS\Frontend\Subscriber as FrontendSubscriber;
use WP_Rocket\Engine\Optimization\RUCSS\Jobs\{Manager, Factory};
use WP_Rocket\Engine\Optimization\RUCSS\Context\RUCSSContextSaas;
use WP_Rocket\Engine\Optimization\RUCSS\Cron\Subscriber as CronSubscriber;

/**
 * Service provider for the WP Rocket RUCSS
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Array of services provided by this service provider
	 *
	 * @var array
	 */
	protected $provides = [
		'rucss_usedcss_table',
		'rucss_settings',
		'rucss_database',
		'rucss_option_subscriber',
		'rucss_admin_subscriber',
		'rucss_used_css',
		'rucss_used_css_query',
		'rucss_frontend_subscriber',
		'rucss_queue',
		'rucss_filesystem',
		'rucss_used_css_controller',
		'rucss_manager',
		'rucss_context_saas',
		'rucss_factory',
		'rucss_cron_subscriber',
		'rucss_context',
		'rucss_optimize_context',
	];

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id The id of the service.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers the option array in the container
	 *
	 * @return void
	 */
	public function register(): void {

		$this->getContainer()->addShared( 'rucss_usedcss_table', UsedCSSTable::class );
		$this->getContainer()->add( 'rucss_database', Database::class )
			->addArgument( 'rucss_usedcss_table' );

		$this->getContainer()->add( 'rucss_settings', Settings::class )
			->addArguments(
				[
					'options',
					'beacon',
					'rucss_usedcss_table',
				]
			);
		$this->getContainer()->add( 'rucss_used_css_query', UsedCSSQuery::class );
		$this->getContainer()->add( 'rucss_queue', Queue::class );
		$this->getContainer()->add( 'rucss_filesystem', Filesystem::class )
			->addArguments(
				[
					new StringArgument( rocket_get_constant( 'WP_ROCKET_USED_CSS_PATH', '' ) ),
					rocket_direct_filesystem(),
				]
			);
		$this->getContainer()->add( 'rucss_context', RUCSSContext::class )
			->addArguments(
				[
					'options',
					'rucss_filesystem',
				]
			);
		$this->getContainer()->add( 'rucss_optimize_context', RUCSSOptimizeContext::class )
			->addArgument( 'options' );
		$this->getContainer()->add( 'rucss_context_saas', RUCSSContextSaas::class )
			->addArgument( 'options' );
		$this->getContainer()->add( 'rucss_manager', Manager::class )
			->addArguments(
				[
					'rucss_used_css_query',
					'rucss_filesystem',
					'rucss_context_saas',
					'options',
				]
				);
		$this->getContainer()->addShared( 'rucss_factory', Factory::class )
			->addArguments(
					[
						'rucss_manager',
						'rucss_usedcss_table',
					]
				);
		$this->getContainer()->add( 'rucss_used_css_controller', UsedCSSController::class )
			->addArguments(
				[
					'options',
					'rucss_used_css_query',
					'dynamic_lists_defaultlists_data_manager',
					'rucss_filesystem',
					'rucss_context',
					'rucss_manager',
				]
			);
		$this->getContainer()->addShared( 'rucss_option_subscriber', OptionSubscriber::class )
			->addArgument( 'rucss_settings' );
		$this->getContainer()->addShared( 'rucss_admin_subscriber', AdminSubscriber::class )
			->addArguments(
				[
					'rucss_settings',
					'rucss_database',
					'rucss_used_css_controller',
					'rucss_queue',
				]
			);
		$this->getContainer()->addShared( 'rucss_frontend_subscriber', FrontendSubscriber::class )
			->addArguments(
				[
					'rucss_used_css_controller',
					'rucss_context',
				]
			);
		$this->getContainer()->addShared( 'rucss_cron_subscriber', CronSubscriber::class )
			->addArguments(
				[
					'job_processor',
					'rucss_used_css_query',
				]
			);
	}
}
