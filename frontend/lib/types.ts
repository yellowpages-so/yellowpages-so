export type Category = {
  id: string;
  public_id: string;
  parent_id?: string | null;
  name: string;
  name_so?: string | null;
  slug: string;
  description?: string | null;
  icon?: string | null;
  featured?: boolean;
  children?: Category[];
};

export type Business = {
  id: string;
  public_id: string;
  legal_name?: string;
  trading_name: string;
  slug: string;
  short_description?: string | null;
  description?: string | null;
  logo_url?: string | null;
  cover_url?: string | null;
  website_url?: string | null;
  status?: string;
  verification_level_id?: string | null;
  is_verified?: boolean;
  average_rating?: string | number | null;
  review_count?: number;
  profile_completeness?: number;
  city?: string | null;
  district?: string | null;
  region?: string | null;
  branch?: {
    id: string;
    name: string;
    phone?: string | null;
    email?: string | null;
    is_head_office?: boolean;
    status?: string | null;
    address_id?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    landmark?: string | null;
    postal_code?: string | null;
    region_id?: string | null;
    region_name?: string | null;
    region_name_so?: string | null;
    city_id?: string | null;
    city_name?: string | null;
    city_name_so?: string | null;
    district_id?: string | null;
    district_name?: string | null;
    district_name_so?: string | null;
  } | null;
  opening_hours?: Array<{
    weekday: number;
    opens_at?: string | null;
    closes_at?: string | null;
    is_closed: boolean;
  }>;
  categories?: Array<{
    name: string;
    name_so?: string | null;
    slug: string;
    is_primary?: boolean;
  }>;
  services?: Array<{
    id?: string;
    name?: string | null;
    name_so?: string | null;
    slug?: string | null;
    custom_name?: string | null;
    description?: string | null;
    price_from?: string | number | null;
    currency?: string | null;
  }>;
  contacts?: Array<{
    contact_type: string;
    label?: string | null;
    value: string;
    is_primary?: boolean;
  }>;
};
export type Review = {
id: string;
rating: number;
title?: string | null;
body?: string | null;
reviewer_name?: string | null;
verified_customer?: boolean;
helpful_count?: number;
business_reply?: string | null;
};
export type SearchResponse = {
  success: boolean;
  data: {
    current_page?: number;
    data: Business[];
    last_page?: number;
    per_page?: number;
    total?: number;
  };
};

export type PaginatedCategories = {
  data: Category[];
};
