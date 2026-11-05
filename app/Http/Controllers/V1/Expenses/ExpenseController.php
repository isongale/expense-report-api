<?php

namespace App\Http\Controllers\V1\Expenses;

use App\Actions\Expenses\CreateExpense;
use App\Actions\Expenses\DeleteExpense;
use App\Actions\Expenses\UpdateExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Expenses\StoreExpenseRequest;
use App\Http\Requests\V1\Expenses\UpdateExpenseRequest;
use App\Http\Resources\V1\Expenses\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * The user's own expenses (drafts and their history). Lists for managers and admins come later.
 */
final class ExpenseController extends Controller implements HasMiddleware
{
    /**
     * Authorization as middleware: it runs after route model binding and before the Form Request,
     * so an unauthorized user gets 403 without learning anything from validation errors.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Expense::class, only: ['index']),
            new Middleware('can:create,'.Expense::class, only: ['store']),
            new Middleware('can:view,expense', only: ['show']),
            new Middleware('can:update,expense', only: ['update']),
            new Middleware('can:delete,expense', only: ['destroy']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $expenses = $request->user()->expenses()
            ->with('expenseCategory')
            // The id breaks ties between expenses of the same day: page boundaries stay stable.
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15);

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request, CreateExpense $createExpense): JsonResponse
    {
        $expense = $createExpense->handle($request->user(), $request->validated());

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        return new ExpenseResource($expense->load('expenseCategory'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, UpdateExpense $updateExpense): ExpenseResource
    {
        $expense = $updateExpense->handle($expense, $request->validated());

        return new ExpenseResource($expense->load('expenseCategory'));
    }

    public function destroy(Expense $expense, DeleteExpense $deleteExpense): Response
    {
        $deleteExpense->handle($expense);

        return response()->noContent();
    }
}
