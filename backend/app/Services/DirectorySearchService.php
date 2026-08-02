<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DirectorySearchService
{
    public function search(array $f): LengthAwarePaginator
    {
        $q = DB::table('directory.businesses as b')->leftJoin('directory.cities as c', 'c.id', '=', 'b.primary_city_id')->leftJoin('directory.addresses as a', 'a.id', '=', 'b.primary_address_id')->leftJoin('directory.districts as d', 'd.id', '=', 'a.district_id')->whereNull('b.deleted_at');
        if (! empty($f['q'])) {
            $t = trim($f['q']);
            $q->where(fn ($x) => $x->whereRaw("b.search_document @@ plainto_tsquery('simple', ?)", [$t])->orWhere('b.trading_name', 'ilike', "%$t%")->orWhere('b.legal_name', 'ilike', "%$t%"));
        }
        if (! empty($f['city'])) {
            $q->where('c.slug', $f['city']);
        } if (! empty($f['district'])) {
            $q->where('d.slug', $f['district']);
        }
        if (! empty($f['category'])) {
            $q->whereExists(fn ($s) => $s->selectRaw('1')->from('directory.business_categories as bc')->join('directory.categories as cat', 'cat.id', '=', 'bc.category_id')->whereColumn('bc.business_id', 'b.id')->where('cat.slug', $f['category']));
        }

        return $q->select(['b.public_id', 'b.trading_name', 'b.legal_name', 'b.slug', 'b.short_description', 'b.logo_url', 'b.average_rating', 'b.review_count', 'b.profile_completeness', 'c.name as city_name', 'd.name as district_name'])->orderByDesc('b.profile_completeness')->orderByDesc('b.average_rating')->paginate($f['per_page'] ?? 15);
    }
}
