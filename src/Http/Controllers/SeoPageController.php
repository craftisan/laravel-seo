<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     7 February 2020
 */

namespace Craftisan\Seo\Http\Controllers;

use Carbon\Carbon;
use Craftisan\Seo\Dictionary\SeoPageStatus;
use Craftisan\Seo\Extensions\Export\SeoPageExport;
use Craftisan\Seo\Extensions\Form;
use Craftisan\Seo\Helpers\SeoTemplateHelper;
use Craftisan\Seo\Jobs\GenerateSitemap;
use Craftisan\Seo\Models\SeoPage;
use Craftisan\Seo\Models\SeoPageVariable;
use Craftisan\Seo\Models\SeoTemplate;
use Craftisan\Seo\Models\SeoTemplateVariable;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

/**
 * Class SeoPageController
 * @package Craftisan\Seo\Http\Controllers
 */
class SeoPageController extends BaseAdminController
{

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Seo Pages from templates';

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    /**
     * @param mixed $id
     * @param \Encore\Admin\Layout\Content $content
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show($id, Content $content)
    {
        // Check if page preview is enabled in config, redirect to admin otherwise
        if (!config('seo.preview.enabled', false)) {
            $warning = new MessageBag([
                'title' => trans('seo::errors.general_warning'),
                'message' => trans('seo::errors.preview_not_enabled'),
            ]);

            return redirect()->route('seo.pages.index')->with(compact('warning'));
        }

        // Check if a view is defined for page preview
        $view = config('seo.preview.page');
        if (empty($view)) {
            $warning = new MessageBag([
                'title' => trans('seo::errors.general_warning'),
                'message' => trans('seo::errors.preview_page_missing'),
            ]);

            return redirect()->route('seo.pages.index')->with(compact('warning'));
        }

        // Check if the requested page exists, redirect to admin otherwise
        $page = SeoPage::with('users')->where('id', $id)->first();
        if (empty($page)) {
            return redirect()->route('seo.pages.index');
        }

        // To keep the page layout consistent
        if ($page->users->count() < 12) {
            $page->users = $page->users->take(6);
        }

        $links = SeoPage::where([
            'status' => SeoPageStatus::LIVE,
            'template_id' => $page->template_id,
        ])->inRandomOrder()->limit(12)->pluck('name', 'url')->all();

        if (count($links) < 12) {
            $links = array_merge(
                $links,
                SeoPage::where('status', SeoPageStatus::LIVE)
                    ->inRandomOrder()
                    ->limit(12 - count($links))
                    ->pluck('name', 'url')
                    ->all()
            );
        }

        return view($view, compact('page', 'links'));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->body($this->form())
            ->title('Create Page');
    }

    /**
     * Make a form builder.
     *
     * @param int|null $id Optional Id to be passed only in case of editing
     *
     * @return \Craftisan\Seo\Extensions\Form
     */
    protected function form(int $id = null)
    {
        $form = new Form(new SeoPage);

        // If template_id is selected or if the form is being edited then templateId would not be null
        if (request()->filled('template_id')) {
            $template = SeoTemplate::with('variables')->where('id', request('template_id'))->first();
        } elseif ($id != null) {
            $template = SeoPage::with('template.variables')->where('id', $id)->first()->template;
        } else {
            $template = null;
        }

        // Show select field for template and variables in the form if template is selected
        // Show only a disabled field while editing the page
        if ($form->isEditing()) {
            $form->text('template.name', 'Template')->disable();
        } else {
            // Show a select field for template and multiple select field for variables
            $form->select('template_id', 'Select Template')
                ->setScript($this->getTemplateOnChangeScript())
                ->options($this->getAllTemplates())
                ->autofocus()
                ->value($template ? $template->id : null);
            if (!empty($template)) {
                foreach ($template->variables as $variable) {
                    $data = $variable->getData();

                    // If the variable has other dependent variable then show it as single select and load the dependent variable's values via api
                    if (!empty($variable->dependent_variable) && !empty($dependent = $template->variables->where('id',
                            $variable->dependent_variable)->first())) {
                        $form->select($variable->name, $variable->name)
                            ->options($data)
                            ->required()
                            ->load($dependent->name, '/admin/seo/api/' . $dependent->name);
                    } else {
                        $form->multipleSelect($variable->name, $variable->name)->options($data)->required();
                    }
                }
            }
        }

        $form->divider();
        $form->text('name', 'Name')->rules('required|max:100')->required()->value($template ? $template->name : null);
        $form->text('url', 'Url')->setScript($this->getUrlScript('name'))->rules('required')->required()->value($template ? $template->url : null);

        if ($form->isCreating() && !empty($template) && $template->parent_url != null) {
            $form->select('parent_url', 'Parent Url')->options(SeoTemplateVariable::getUrlVariables())->value($template->parent_url);
        } else {
            $form->text('parent_url');
        }

        $form->text('redirect_url', 'Redirect Url')->rules('required')->required()
            ->help('This wil be a permanent redirect (HTTP 301), <a href="https://moz.com/blog/301-redirection-rules-for-seo">recommended for seo</a>');

        $form->text('meta_title', 'Meta title')
            ->placeholder('Max 100 characters')
            ->rules('max:100')
            ->help('Optimal Length: 50-60 characters. Max: 100 characters.')
            ->value($template ? $template->meta_title : null);
        $form->textarea('meta_description', 'Meta description')
            ->help('Optimal Length: 150-160 characters. Max: 320 characters.')
            ->value($template ? $template->meta_description : null);
        $form->textarea('h1', 'H1')->value($template ? $template->h1 : null);
        $form->textarea('h2', 'H2')->value($template ? $template->h2 : null);
        $form->textarea('h3', 'H3')->value($template ? $template->h3 : null);
        $form->textarea('p1', 'P1')->value($template ? $template->p1 : null);
        $form->textarea('p2', 'P2')->value($template ? $template->p2 : null);
        $form->tags('keywords', 'Keywords')->value($template ? $template->keywords : null);
        $form->switch('status', 'Status')->states([
            'off' => ['value' => SeoPageStatus::DRAFT, 'text' => SeoPageStatus::DRAFT, 'color' => 'primary'],
            'on' => ['value' => SeoPageStatus::LIVE, 'text' => SeoPageStatus::LIVE, 'color' => 'success'],
        ])->value(SeoPageStatus::DRAFT);

        // Operations to perform after submitting and before actually saving the data
        $form->submitted(function (Form $form) use ($template) {
            if (!empty($template)) {
                // Ignore saving variables to the table, they are just for parsing the fields
                $form->ignore($template->variables->pluck('name')->all());
            }
            $form->model()->author_id = Admin::user()->getAuthIdentifier();

            return $form;
        });

        if ($form->isUpdating()) {
            $this->processEditForm($form);
        } else {
            $this->processCreateForm($form, $template);
        }

        if (config('seo.routes.sitemap')) {
            $form->saved(function (Form $form) {
                dispatch(new GenerateSitemap());
            });
        }

        return $form;
    }

    private function getTemplateOnChangeScript()
    {
        return <<<SCRIPT

$('.template_id').change(function () {
    window.location.href = window.location.pathname+"?"+$.param({'template_id':$(this).val()});
});

SCRIPT;
    }

    /**
     * @return array
     */
    private function getAllTemplates()
    {
        return SeoTemplate::all()->pluck('name', 'id')->all();
    }

    /**
     * Return the script to convert the a given string into url friendly string
     *
     * @param string $identifier
     *
     * @return string
     */
    private function getUrlScript(string $identifier)
    {
        return <<<SCRIPT

$('#{$identifier}').on('input', function () {
    var val = $(this).val();
    val = val.replace(/\s+/g, '-').toLowerCase().replace(/\//g, '-');
    $('#url').val(val);
});

SCRIPT;
    }

    /**
     * Method to edit the form through variable replacing algorithm
     *
     * @param \Craftisan\Seo\Extensions\Form $form
     */
    private function processEditForm(Form $form)
    {
        $form->saving(function (Form $form) {
            foreach (app(SeoTemplate::class)->getFillable() as $attribute) {

                // Original input value (with variables)
                $input = $form->input($attribute);
                if ($input == null) {
                    continue;
                }

                // Check if the field has any variables and throw error since variables are not allowed while editing
                $variables = SeoTemplateHelper::extractVariablesFromString($input);
                if ($variables instanceof Response || count($variables)) {
                    $error = new MessageBag([
                        'title' => trans('seo::errors.page_error'),
                        'message' => trans('seo::errors.variable_not_allowed'),
                    ]);

                    return back()->with(compact('error'))->withInput();
                }
            }
            // Format url properly
            $form->input('url', str_replace([' ', '_', '/', '\\', ','], '-', strtolower($form->model()->url)));
            $form->input('parent_url', str_replace([' ', '_', '\\', ','], '-', strtolower($form->model()->parent_url)));
            $form->input('redirect_url', str_replace([' ', '_', '\\', ','], '-', strtolower($form->model()->redirect_url)));

            return $form;
        });
    }

    /**
     * Method to process the form through variable replacing algorithm
     *
     * @param \Craftisan\Seo\Extensions\Form $form
     * @param \Craftisan\Seo\Models\SeoTemplate|null $template
     */
    private function processCreateForm(Form $form, SeoTemplate $template = null)
    {
        // Operations (parsing the field variables) to perform before saving data
        $form->saving(function (Form $form) use ($template) {
            if (empty($template)) {
                return $form;
            }

            // Check if value of all the variables is provided in teh input, throw error otherwise
            $variableValues = [];
            $error = false;
            foreach ($template->variables as $variable) {
                $input = request($variable->name);
                if ($input == null || empty($input)) {
                    $error = true;
                    // Break out of the loop even if a single variable's value is not provided, hence saving the iterations
                    break;
                }
                $variableValues[$variable->name] = $input;
            }

            // Redirect back with errors if any/all variable values are not provided
            if ($error || empty($variableValues)) {
                $error = new MessageBag([
                    'title' => trans('seo::errors.page_error'),
                    'message' => trans('seo::errors.variable_value_empty'),
                ]);

                return back()->with(compact('error'))->withInput();
            }

            $pageTemplate = new SeoPage();
            foreach (app(SeoTemplate::class)->getFillable() as $attribute) {

                // Original input value (with variables)
                $input = $form->input($attribute);
                if ($input == null) {
                    continue;
                }

                // Get all the variables from the input field
                $fieldVariables = SeoTemplateHelper::extractVariablesFromString($input);
                $pageVariables[] = $fieldVariables;

                if (!is_array($fieldVariables) || $fieldVariables instanceof Response) {
                    return $fieldVariables;
                }

                if (count($fieldVariables) > count($variableValues)) {
                    $error = new MessageBag([
                        'title' => trans('seo::errors.page_error'),
                        'message' => trans('seo::errors.variable_mismatch'),
                    ]);

                    return back()->with(compact('error'))->withInput();
                }

                $pageTemplate->$attribute = $input;
            }

            $pageTemplate->template_id = $form->input('template_id');
            $pageTemplate->status = $form->input('status') === 'on' ? SeoPageStatus::LIVE : SeoPageStatus::DRAFT;
            $pageTemplate->author_id = Admin::user()->getAuthIdentifier();

            $newVariables = [];
            if (!empty($pageVariables)) {
                foreach ($pageVariables as $variable) {
                    $newVariables = array_merge($newVariables, array_values($variable));
                }
                $newVariables = array_values(array_unique($newVariables));
            }

            $urlError = [];
            if (!empty($variableValues)) {
                $pages = [];
                for ($i = 0; $i < count($newVariables); $i++) {
                    if ($i == 0) {
                        $pages = $this->createPages($variableValues, $newVariables[$i], $pageTemplate);
                    } else {
                        $pages = $this->createPages($variableValues, $newVariables[$i], $pageTemplate, $pages);
                    }
                }

                foreach ($pages as $key => $page) {

                    $pageVariableValues = $page->variable_values;
                    unset($page->variable_values); // Detach the temp seoUsers object

                    // If page with the same url exists, don't save the current page, move on to the next
                    if (!$this->formatUrl($page, $form)) {
                        $urlError[] = trans('seo::errors.url_exists_page', ['url' => $page->full_url]);
                        continue;
                    }

                    // Save the page
                    $page->save();
                    $page->variables()->save($pageVariableValues);
                }
            }

            $form->model()->deleted_at = Carbon::now()->toDateTimeString();

            if (!empty($urlError)) {
                $form->warning = [
                    'title' => trans('seo::errors.page_warning'),
                    'message' => implode('<br>', array_values($urlError)),
                ];
            }

            return $form;
        });
    }

    /**
     * Create SeoPage objects by parsing through each value provided for the users
     *
     * @param $variableValues
     * @param $variable
     * @param \Illuminate\Database\Eloquent\Model $pageTemplate
     * @param array $previousCollection
     *
     * @return array
     */
    private function createPages($variableValues, $variable, Model $pageTemplate, array $previousCollection = [])
    {
        $pages = [];
        $values = array_filter(is_array($variableValues[$variable]) ? $variableValues[$variable] : [$variableValues[$variable]]);
        foreach ($values as $value) {
            if ($previousCollection == null) {
                $replica = clone $pageTemplate;
                $this->setAttributes($replica, $variable, $value);
                // Attach user queries to the builder object on each iteration
                $this->attachUserQueryParams($replica, $variable, $value);
                $pages[] = $replica;
            } else {
                foreach ($previousCollection as $key => $previousPage) {
                    $replica = clone $previousPage;
                    $this->setAttributes($replica, $variable, $value);
                    // Attach user queries to the builder object on each iteration
                    $this->attachUserQueryParams($replica, $variable, $value);
                    $pages[] = $replica;
                }
            }
        }

        return $pages;
    }

    /**
     * Set each attribute on the SeoPage model after replacing $variable with $value
     *
     * @param $page
     * @param $variable
     * @param $value
     */
    private function setAttributes($page, $variable, $value)
    {
        foreach ($page->getFillable() as $attribute) {
            $page->$attribute = str_replace('{{' . $variable . '}}', $value, $page->$attribute);
        }
    }

    /**
     * Attach a collection to the SeoPage model object which contains the params required to query users to associate with the page
     *
     * @param \Craftisan\Seo\Models\SeoPage $page
     * @param $variable
     * @param $value
     */
    private function attachUserQueryParams($page, $variable, $value)
    {
        if (!isset($page->variable_values)) {
            $page->variable_values = new SeoPageVariable();
            $page->variable_values->variables = [$variable => $value];
        } else {
            $page->variable_values->variables = array_merge($page->variable_values->variables, [$variable => $value]);
        }
    }

    /**
     * Formats url and checks if a page with the same url already exists, if it doesn't return true, false otherwise
     *
     * @param \Craftisan\Seo\Models\SeoPage $page
     * @param \Craftisan\Seo\Extensions\Form $form
     *
     * @return bool
     */
    private function formatUrl($page, Form $form)
    {
        // Format url properly
        $page->url = str_replace([' ', '_', '/', '\\', ','], '-', strtolower($page->url));
        $page->parent_url = str_replace([' ', '_', '\\', ','], '-', strtolower($page->parent_url));
        $page->redirect_url = str_replace([' ', '_', '\\', ','], '-', strtolower($page->redirect_url));

        // Check if a page of the same url exists
        $oldPages = SeoPage::where('url', $page->url)->where('parent_url', $page->parent_url);

        // Take the model id (not null while editing)
        if ($form->model()->id != null) { // id will not be null while editing, so check if the url exists for any other page, if yes then do not proceed
            $oldPages = $oldPages->where('id', '!=', $form->model()->id)->get();
        } else {
            // Get the pages
            $oldPages = $oldPages->get();
        }

        return $oldPages->isEmpty();
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title('Edit Page')
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form($id)->edit($id));
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SeoPage);

        $grid->column('id');
        $grid->column('name', 'Name');
        $grid->column('meta_title', 'Meta title')->display(function ($title) {
            return Str::limit($title, 20, '...');
        });
        $grid->column('full_url', 'Url')->display(function ($url) {
            if ($this->status === SeoPageStatus::LIVE) {
                $fullUrl = config('seo.live_url') . $url;

                return "<a href='$fullUrl'>$url</a>";
            }

            return $url;
        });
        $grid->column('status', 'Status')->switch([
            'on' => ['value' => SeoPageStatus::LIVE, 'text' => SeoPageStatus::LIVE, 'color' => 'success'],
            'off' => ['value' => SeoPageStatus::DRAFT, 'text' => SeoPageStatus::DRAFT, 'color' => 'default'],
        ]);
        $grid->column('keywords')->display(function ($keywords) {
            $keywordsFormat = [];
            foreach ($keywords as $keyword) {
                $keywordsFormat[] = "<span class='label label-success'>$keyword</span>";
            }

            return implode(' ', $keywordsFormat);
        });
        $grid->column('template', 'Template')->display(function ($template) {
            if ($template != null) {
                $id = $template['id'];
                $name = $template['name'];

                return "<a href='templates/$id/edit'>$name</a>";
            }
        });
        $grid->column('author.name', 'Author');
        $grid->column('created_at');
        $grid->column('updated_at');

        // Set the export class
        $grid->exporter(new SeoPageExport());

        // Template name filter
        $grid->filter(function (Grid\Filter $filter) {
            $filter->where(function ($query) {
                $query->whereHas('template', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Template Name');
        });

        return $grid;
    }
}
