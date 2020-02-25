<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     7 February 2020
 */

namespace Craftisan\Seo\Http\Controllers;

use Craftisan\Seo\Extensions\Export\SeoTemplateExport;
use Craftisan\Seo\Extensions\Form;
use Craftisan\Seo\Helpers\SeoTemplateHelper;
use Craftisan\Seo\Models\SeoTemplate;
use Craftisan\Seo\Models\SeoTemplateVariable;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Displayers\Actions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SeoTemplateController
 * @package Craftisan\Seo\Http\Controllers
 */
class SeoTemplateController extends BaseAdminController
{

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SeoTemplate);

        $grid->column('id');

        $grid->column('name', 'Name')->display(function ($name) {
            return "<a href='pages/create?template_id=$this->id'>$name</a>";
        });

        $grid->column('meta_title', 'Meta title')->display(function ($text) {
            return Str::limit($text, 20, '...');
        });

        $grid->column('full_url', 'Url');

        $grid->column('keywords')->display(function ($keywords) {
            $keywordsFormat = [];
            foreach ($keywords as $keyword) {
                $keywordsFormat[] = "<span class='label label-success'>$keyword</span>";
            }

            return implode(' ', $keywordsFormat);
        });

        $grid->column('author.name', 'Author');
        $grid->column('created_at');
        $grid->column('updated_at');

        $this->createDuplicateButton($grid);

        // Set the export class
        $grid->exporter(new SeoTemplateExport());

        $grid->actions(function (Actions $actions) {
            $actions->disableView();
        });

        return $grid;
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
            ->row(function (Row $row) use ($id) {
                $row->column('8', $this->form()->edit($id));
                $row->column('4', $this->getTemplateVariables());
            })
            ->header('Edit Template')
            ->description('description');
    }

    /**
     * Make a form builder.
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SeoTemplate);

        $form->text('name', 'Name')->rules('required|max:100')->required();
        $form->text('url', 'Url')->setScript($this->getUrlScript('name'))->rules('required')->required();
        $form->select('parent_url', 'Parent Url')->options(SeoTemplateVariable::getUrlVariables());
        $form->text('meta_title', 'Meta title')
            ->placeholder('Max 100 characters')
            ->rules('max:100')
            ->help('Optimal Length: 50-60 characters. Max: 100 characters.');
        $form->textarea('meta_description', 'Meta description')
            ->help('Optimal Length: 150-160 characters. Max: 320 characters.');
        $form->textarea('h1', 'H1');
        $form->textarea('h2', 'H2');
        $form->textarea('h3', 'H3');
        $form->textarea('p1', 'P1');
        $form->textarea('p2', 'P2');
        $form->tags('keywords', 'Keywords');

        // Set the author Id
        $form->model()->author_id = Admin::user()->getAuthIdentifier();

        $form->saving(function (Form $form) {

            // Parse all the variables from all the input fields
            $variables = SeoTemplateHelper::getVariablesFromTemplateInput($form);

            if ($variables instanceof Response) {
                return $variables;
            }

            if (count($variables) == 0) {
                $warning = new MessageBag([
                    'title' => trans('seo::errors.template_error'),
                    'message' => trans('seo::errors.no_variables_warning'),
                ]);

                return back()->with(compact('warning'))->withInput();
            }

            // Get the models of the variables from db
            $seoVariables = SeoTemplateVariable::whereIn('name', $variables)->get();

            // Check if all the variables in the input actually exist in the db
            $diff = array_diff($variables, $seoVariables->pluck('name')->all());

            if (count($diff) != 0) {
                $error = new MessageBag([
                    'title' => trans('seo::errors.template_error'),
                    'message' => trans('seo::errors.variable_spellcheck',
                        ['variable' => '{{' . last($diff) . '}}']),
                ]);

                return back()->with(compact('error'))->withInput();
            }
        });

        $form->saved(function (Form $form) {
            // Parse all the variables from all the input fields
            $seoVariables = SeoTemplateHelper::getVariablesFromTemplateInput($form);

            if ($seoVariables instanceof Response) {
                return $seoVariables;
            }

            // Associate the variables with the template
            $seoVariables = SeoTemplateVariable::whereIn('name', $seoVariables)->get();
            $form->model()->variables()->sync($seoVariables); // with detaching

            // Set redirect
            $redirect = redirect('/admin/seo/templates');

            // To generate warning if the url of the same pattern already exists
            // Check if a template of the same url pattern exists
            $templates = SeoTemplate::where('url', $form->model()->url)->where('id', '!=', $form->model()->id)->get();
            if ($templates->isNotEmpty()) {
                $warning = new MessageBag([
                    'title' => trans('seo::errors.template_warning'),
                    'message' => trans('seo::errors.url_exists_template'),
                ]);

                // set warning in the redirect
                $redirect->with(compact('warning'));
            }

            return $redirect;
        });

        return $form;
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

    private function getTemplateVariables()
    {

        $variables = SeoTemplateVariable::all();

        $data = [];
        foreach ($variables as $variable) {

            $form = new \Encore\Admin\Widgets\Form();
            $form->disableReset();
            $form->disableSubmit();

            $select = $form->select($variable->name, $variable->name);

            $select->options($variable->getData());

            $data[] = [$select];
        }

        $table = new Table([], $data);

        return (new Box('Variable Reference', $table))->style('warning')->solid();
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
            ->row(function (Row $row) {
                $row->column('8', $this->form());
                $row->column('4', $this->getTemplateVariables());
            })
            ->header('Create Template');
    }

    public function duplicate($id)
    {
        $response = parent::duplicate($id);

        $warning = new MessageBag([
            'title' => trans('seo::errors.template_warning'),
            'message' => trans('seo::errors.url_exists_template'),
        ]);

        // set warning in the redirect
        $response->with(compact('warning'));

        return $response;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(SeoTemplate::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->meta_title('Meta title');
        $show->meta_description('Meta description');
        $show->h1('H1');
        $show->h2('H2');
        $show->h3('H3');
        $show->p1('P1');
        $show->p2('P2');
        $show->url('Url');
        $show->keywords('Keywords');

        return $show;
    }
}
