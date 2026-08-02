<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DirectorySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicDirectoryController extends Controller
{
    public function __construct(private readonly DirectorySearchService $searchService) {}

    public function search(Request $request): JsonResponse
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:255'], 'district' => ['nullable', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:255'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);

        return response()->json(['success' => true, 'data' => $this->searchService->search($f)]);
    }

    public function show(string $slug): JsonResponse
    {
        $b = DB::table('directory.businesses as b')->leftJoin('directory.cities as c', 'c.id', '=', 'b.primary_city_id')->where('b.slug', $slug)->whereNull('b.deleted_at')->select(['b.*', 'c.name as city_name'])->first();
        abort_if(! $b, 404, 'Business not found.');
        $b->categories = DB::table('directory.business_categories as bc')->join('directory.categories as c', 'c.id', '=', 'bc.category_id')->where('bc.business_id', $b->id)->get(['c.name', 'c.name_so', 'c.slug', 'bc.is_primary']);
        $b->services = DB::table('directory.business_services as bs')->leftJoin('directory.services as s', 's.id', '=', 'bs.service_id')->where('bs.business_id', $b->id)->where('bs.active', true)->get(['s.name', 's.name_so', 's.slug', 'bs.custom_name', 'bs.description', 'bs.price_from', 'bs.currency']);
        $b->contacts = DB::table('directory.business_contacts')->where('business_id', $b->id)->where('is_public', true)->get(['contact_type', 'label', 'value', 'is_primary']);

        return response()->json(['success' => true, 'data' => $b]);
    }
}
