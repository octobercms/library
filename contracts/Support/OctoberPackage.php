<?php namespace October\Contracts\Support;

/**
 * OctoberPackage
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface OctoberPackage
{
    /**
     * registerMarkupTags registers Twig markup tags introduced by this package.
     *
     *     return [
     *         'filters' => [],
     *         'functions' => []
     *     ];
     *
     * @return array
     */
    public function registerMarkupTags();

    /**
     * registerComponents registers any CMS components implemented in this package.
     *
     *     return [
     *        \Acme\Demo\Components\LocalePicker::class => 'localePicker',
     *     ];
     *
     * @return array
     */
    public function registerComponents();

    /**
     * registerPageSnippets registers any CMS snippets implemented in this package.
     *
     *     return [
     *        \Acme\Demo\Components\YouTubeVideo::class => 'youtubeVideo',
     *     ];
     *
     * @return array
     */
    public function registerPageSnippets();

    /**
     * registerContentFields registers content fields used by tailor implemented in this package.
     *
     *     return [
     *        \Tailor\ContentFields\TextareaField::class => 'textarea',
     *     ];
     *
     * @return array
     */
    public function registerContentFields();

    /**
     * registerNavigation registers backend navigation items for this package.
     *
     *     return [
     *         'blog' => []
     *     ];
     *
     * @return array
     */
    public function registerNavigation();

    /**
     * registerPermissions registers any permissions used by this package.
     *
     *     return [
     *         'general.backend' => [
     *             'label' => 'Access the Backend Panel',
     *             'tab' => 'General'
     *         ],
     *     ];
     *
     * @return array
     */
    public function registerPermissions();

    /**
     * registerSettings registers any backend configuration links used by this package.
     *
     *     return [
     *         'updates' => []
     *     ];
     *
     * @return array
     */
    public function registerSettings();

    /**
     * registerReportWidgets registers any report widgets provided by this package.
     * The widgets must be returned in the following format:
     *
     *     return [
     *         'className1' => [
     *             'label' => 'My widget 1',
     *             'context' => ['context-1', 'context-2'],
     *         ],
     *         'className2' => [
     *             'label' => 'My widget 2',
     *             'context' => 'context-1'
     *         ]
     *     ];
     *
     * @return array
     */
    public function registerReportWidgets();

    /**
     * registerFormWidgets registers any form widgets implemented in this package.
     * The widgets must be returned in the following format:
     *
     *     return [
     *         ['className1' => 'alias'],
     *         ['className2' => 'anotherAlias']
     *     ];
     *
     * @return array
     */
    public function registerFormWidgets();

    /**
     * registerFilterWidgets registers any filter widgets implemented in this package.
     * The widgets must be returned in the following format:
     *
     *     return [
     *         ['className1' => 'alias'],
     *         ['className2' => 'anotherAlias']
     *     ];
     *
     * @return array
     */
    public function registerFilterWidgets();

    /**
     * registerListColumnTypes registers custom backend list column types introduced
     * by this package.
     *
     * @return array
     */
    public function registerListColumnTypes();

    /**
     * registerMailLayouts registers any mail layouts implemented by this package.
     * The layouts must be returned in the following format:
     *
     *     return [
     *         'marketing' => 'acme.blog::layouts.marketing',
     *         'notification' => 'acme.blog::layouts.notification',
     *     ];
     *
     * @return array
     */
    public function registerMailLayouts();

    /**
     * registerMailTemplates registers any mail templates implemented by this package.
     * The templates must be returned in the following format:
     *
     *     return [
     *         'acme.blog::mail.welcome',
     *         'acme.blog::mail.forgot_password',
     *     ];
     *
     * @return array
     */
    public function registerMailTemplates();

    /**
     * registerMailPartials registers any mail partials implemented by this package.
     * The partials must be returned in the following format:
     *
     *     return [
     *         'tracking' => 'acme.blog::partials.tracking',
     *         'promotion' => 'acme.blog::partials.promotion',
     *     ];
     *
     * @return array
     */
    public function registerMailPartials();

    /**
     * registerSchedule registers scheduled tasks that are executed on a regular basis.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function registerSchedule($schedule);
}
