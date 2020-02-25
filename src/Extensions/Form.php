<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     13 February 2020
 */

namespace Craftisan\Seo\Extensions;

use Closure;
use Encore\Admin\Form as LaravelAdminForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use function request;

/**
 * Class Form
 * Provides additional methods and modifies the behavior of @see \Encore\Admin\Form
 *
 * @package Craftisan\Seo\Extensions
 */
class Form extends LaravelAdminForm
{

    /**
     * @see \Illuminate\Support\MessageBag
     * @var array This array must contain keys: title and message, to be compatible with MessageBag
     */
    public $warning;

    /**
     * Overrides the original @see LaravelAdminForm::store() method
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function store()
    {
        $data = request()->all();

        // Handle validation errors.
        if ($validationMessages = $this->validationMessages($data)) {
            return $this->responseValidationError($validationMessages);
        }

        if (($response = $this->prepare($data)) instanceof Response) {
            return $response;
        }

        if ($this->model->deleted_at != null) {
            return $this->redirectAfterStore();
        }

        DB::transaction(function () {
            $inserts = $this->prepareInsert($this->updates);

            foreach ($inserts as $column => $value) {
                $this->model->setAttribute($column, $value);
            }

            $this->model->save();
            $this->updateRelation($this->relations);
        });

        if (($response = $this->callCreated()) instanceof Response) {
            return $response;
        }

        if (($response = $this->callSaved()) instanceof Response) {
            return $response;
        }

        if ($response = $this->ajaxResponse(trans('admin.save_succeeded'))) {
            return $response;
        }

        return $this->redirectAfterStore();
    }

    protected function redirectAfterStore()
    {
        $response = parent::redirectAfterStore();
        if (!empty($this->warning)) {
            $warning = new MessageBag([
                'title' => $this->warning['title'] ?? 'Warning',
                'message' => $this->warning['message'] ?? 'A warning was generated while saving data',
            ]);

            $response->with(compact('warning'));
        }

        return $response;
    }

    /**
     * Callback after saving a Model.
     *
     * @return mixed|null
     */
    protected function callCreated()
    {
        return $this->callHooks('created');
    }

    /**
     * Duplicate an item
     *
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function duplicate($id)
    {
        // Find the original model
        $model = $this->model->findOrFail($id);

        // Clone the model and save
        $clone = $model->replicate();
        $clone->save();

        return $this->redirectAfterDuplicate($clone->id);
    }

    /**
     * Redirect to the edit page url of the newly cloned item
     *
     * @param $key
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectAfterDuplicate($key)
    {
        return redirect(rtrim($this->resource(-2), '/') . "/{$key}/edit");
    }

    /**
     * Set saved callback.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function created(Closure $callback)
    {
        return $this->registerHook('created', $callback);
    }

    /**
     * Indicates if current form page is updating.
     *
     * @return bool
     */
    public function isUpdating(): bool
    {
        return Str::endsWith(request()->route()->getName(), '.update');
    }

    /**
     * Indicates if current form page is updating.
     *
     * @return bool
     */
    public function isStoring(): bool
    {
        return Str::endsWith(request()->route()->getName(), '.store');
    }
}