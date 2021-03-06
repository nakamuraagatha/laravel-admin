<?php

namespace Kizi\Admin\Controllers;

use Illuminate\Routing\Controller;
use Kizi\Admin\Auth\Database\Administrator;
use Kizi\Admin\Auth\Database\OperationLog;
use Kizi\Admin\Facades\Admin;
use Kizi\Admin\Grid;
use Kizi\Admin\Layout\Content;

class LogController extends Controller
{
    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {
            $content->header(trans('admin::lang.operation_log'));
            $content->description(trans('admin::lang.list'));

            $grid = Admin::grid(OperationLog::class, function (Grid $grid) {
                $grid->model()->orderBy('id', 'DESC');

                $grid->id('ID')->sortable();
                $grid->user()->name();
                $grid->method()->value(function ($method) {
                    $color = array_get(OperationLog::$methodColors, $method, 'grey');

                    return "<span class=\"badge bg-$color\">$method</span>";
                });
                $grid->path()->label('info');
                $grid->ip()->label('primary');
                $grid->input()->value(function ($input) {
                    $input = json_decode($input, true);
                    $input = array_except($input, '_pjax');

                    return '<code>' . json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</code>';
                });

                $grid->created_at(trans('admin::lang.created_at'));

                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableEdit();
                });

                $grid->disableCreation();

                $grid->filter(function ($filter) {
                    $filter->is('user_id', 'User')->select(Administrator::all()->pluck('name', 'id'));
                    $filter->is('method')->select(array_combine(OperationLog::$methods, OperationLog::$methods));
                    $filter->like('path');
                    $filter->is('ip');

                    $filter->useModal();
                });
            });

            $content->body($grid);
        });
    }

    public function destroy($id)
    {
        $ids = explode(',', $id);

        if (OperationLog::destroy(array_filter($ids))) {
            return response()->json([
                'status'  => true,
                'message' => trans('admin::lang.delete_succeeded'),
            ]);
        } else {
            return response()->json([
                'status'  => false,
                'message' => trans('admin::lang.delete_failed'),
            ]);
        }
    }
}
