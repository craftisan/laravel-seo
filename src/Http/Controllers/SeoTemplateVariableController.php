<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     07 February 2020
 */

namespace Craftisan\Seo\Http\Controllers;

use Craftisan\Seo\Extensions\Form;
use Craftisan\Seo\Models\SeoTemplateVariable;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

/**
 * Class SeoTemplateVariableController
 * @package Craftisan\Seo\Http\Controllers
 */
class SeoTemplateVariableController extends BaseAdminController
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
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SeoTemplateVariable);

        $grid->id('Id');
        $grid->name('Name');
        $grid->column('is_url', 'Is Url')->switch()->help('Indicates whether the variable can used as a path string in url');
        $grid->column('url');
        $grid->data_model('Data model');
        $grid->user_relation('Relation with User');
        $grid->user_relation_column('Relational Column');

        return $grid;
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     *
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
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
        $show = new Show(SeoTemplateVariable::findOrFail($id));

        $show->id('Id');
        $show->name('Name');
        $show->data_model('Data model');
        $show->user_relation('Relation with User');
        $show->user_relation_column('Relational Column');
        $show->parent_id();
        $show->is_url();
        $show->created_at('Created at');
        $show->created_at('Created at');
        $show->updated_at('Updated at');
        $show->deleted_at('Deleted at');

        return $show;
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
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SeoTemplateVariable);

        if ($form->isEditing()) {
            $form->text('id')->disable();
        }

        $form->text('name', 'Name')->required();

        $form->select('parent_id', 'Parent')
            ->options(SeoTemplateVariable::where('is_url', true)->get()->pluck('name', 'id')->all())
            ->help('Associates another variable as a parent relationship to prepend the parent\'s name while forming url. Only those variables are available whose \'is_url\' is set to true');

        $form->switch('is_url')->states()
            ->help('Indicates whether the variable can used as a path string in url')
            ->value(true);

        $form->divider('NOTE: Do not edit following fields if you are NOT SURE what they do');
        $form->text('data_model', 'Data model')->required()
            ->help('Indicates the model from where to fetch the data for the variable $name.');

        $form->text('user_relation', 'Relation with User')
            ->help('Indicates the relation from User model with the model where data for the {variable} is stored.');

        $form->text('user_relation_column', 'Relational Column')
            ->help('Indicates the column in the $user_relation table where data for the {variable} is stored.');

        return $form;
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
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

}
