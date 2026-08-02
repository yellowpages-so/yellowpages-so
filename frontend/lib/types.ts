export type Category = {
  id: string;
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
  legal_name?: string;
  trading_name: string;
  slug: string;
  short_description?: string | null;
  description?: string | null;
  logo_url?: string | null;
  cover_url?: string | null;
  website_url?: string | null;
  status?: string;
  average_rating?: string | number | null;
  review_count?: number;
  profile_completeness?: number;
  city?: string | null;
  district?: string | null;
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
