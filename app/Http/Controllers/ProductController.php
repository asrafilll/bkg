<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $productQuery = Product::query();

        if ($request->filled('filter')) {
            $productQuery->where('name', 'LIKE', "%{$request->get('filter')}%");
        }

        $sortables = [
            'name',
            'price',
            'created_at',
        ];
        $sort = 'created_at';
        $direction = 'desc';

        if ($request->filled('sort') && in_array($request->get('sort'), $sortables)) {
            $sort = $request->get('sort');
        }

        if ($request->filled('direction') && in_array($request->get('direction'), ['asc', 'desc'])) {
            $direction = $request->get('direction');
        }

        $products = $productQuery->orderBy($sort, $direction)->paginate();


        return Response::view('product.index', [
            'products' => $products,
        ]);
    }
}
