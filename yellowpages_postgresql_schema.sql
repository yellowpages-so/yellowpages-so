-- YellowPages.so PostgreSQL starter architecture
-- PostgreSQL 16+

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS postgis;

CREATE SCHEMA IF NOT EXISTS iam;
CREATE SCHEMA IF NOT EXISTS directory;
CREATE SCHEMA IF NOT EXISTS verification;
CREATE SCHEMA IF NOT EXISTS reviews;
CREATE SCHEMA IF NOT EXISTS leads;
CREATE SCHEMA IF NOT EXISTS billing;
CREATE SCHEMA IF NOT EXISTS advertising;
CREATE SCHEMA IF NOT EXISTS notifications;
CREATE SCHEMA IF NOT EXISTS analytics;
CREATE SCHEMA IF NOT EXISTS moderation;
CREATE SCHEMA IF NOT EXISTS cms;
CREATE SCHEMA IF NOT EXISTS system;

CREATE OR REPLACE FUNCTION system.set_updated_at()
RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END $$;

-- IAM
CREATE TABLE iam.users (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  public_id text UNIQUE NOT NULL,
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('pending','active','suspended','closed')),
  password_hash text,
  email_verified_at timestamptz,
  phone_verified_at timestamptz,
  last_login_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  deleted_at timestamptz
);

CREATE TABLE iam.user_profiles (
  user_id uuid PRIMARY KEY REFERENCES iam.users(id) ON DELETE CASCADE,
  first_name text,
  last_name text,
  display_name text,
  avatar_url text,
  locale text NOT NULL DEFAULT 'en',
  timezone text NOT NULL DEFAULT 'Africa/Mogadishu',
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE iam.user_emails (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES iam.users(id) ON DELETE CASCADE,
  email citext NOT NULL,
  is_primary boolean NOT NULL DEFAULT false,
  verified_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(email)
);

CREATE TABLE iam.user_phones (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES iam.users(id) ON DELETE CASCADE,
  country_code text NOT NULL,
  phone_number text NOT NULL,
  is_primary boolean NOT NULL DEFAULT false,
  verified_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(country_code, phone_number)
);

CREATE TABLE iam.roles (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  scope text NOT NULL CHECK (scope IN ('platform','business')),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE iam.permissions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE iam.role_permissions (
  role_id uuid REFERENCES iam.roles(id) ON DELETE CASCADE,
  permission_id uuid REFERENCES iam.permissions(id) ON DELETE CASCADE,
  PRIMARY KEY(role_id, permission_id)
);

CREATE TABLE iam.user_roles (
  user_id uuid REFERENCES iam.users(id) ON DELETE CASCADE,
  role_id uuid REFERENCES iam.roles(id) ON DELETE CASCADE,
  assigned_by uuid REFERENCES iam.users(id),
  assigned_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY(user_id, role_id)
);

CREATE TABLE iam.user_sessions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES iam.users(id) ON DELETE CASCADE,
  token_hash text NOT NULL,
  ip_address inet,
  user_agent text,
  expires_at timestamptz NOT NULL,
  revoked_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE iam.login_attempts (
  id bigserial PRIMARY KEY,
  user_id uuid REFERENCES iam.users(id) ON DELETE SET NULL,
  identifier text,
  ip_address inet,
  successful boolean NOT NULL,
  occurred_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE iam.otp_challenges (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES iam.users(id) ON DELETE CASCADE,
  channel text NOT NULL CHECK (channel IN ('sms','email','whatsapp')),
  destination text NOT NULL,
  code_hash text NOT NULL,
  purpose text NOT NULL,
  expires_at timestamptz NOT NULL,
  consumed_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- LOCATIONS
CREATE TABLE directory.countries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  iso2 char(2) UNIQUE NOT NULL,
  iso3 char(3) UNIQUE NOT NULL,
  name text NOT NULL,
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE directory.administrative_areas (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  country_id uuid NOT NULL REFERENCES directory.countries(id),
  parent_id uuid REFERENCES directory.administrative_areas(id),
  area_type text NOT NULL,
  name text NOT NULL,
  slug citext NOT NULL,
  active boolean NOT NULL DEFAULT true,
  UNIQUE(country_id, parent_id, slug)
);

CREATE TABLE directory.cities (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  administrative_area_id uuid REFERENCES directory.administrative_areas(id),
  name text NOT NULL,
  slug citext UNIQUE NOT NULL,
  location geography(Point,4326),
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE directory.districts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  city_id uuid NOT NULL REFERENCES directory.cities(id),
  name text NOT NULL,
  slug citext NOT NULL,
  active boolean NOT NULL DEFAULT true,
  UNIQUE(city_id, slug)
);

CREATE TABLE directory.neighbourhoods (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  district_id uuid NOT NULL REFERENCES directory.districts(id),
  name text NOT NULL,
  slug citext NOT NULL,
  active boolean NOT NULL DEFAULT true,
  UNIQUE(district_id, slug)
);

CREATE TABLE directory.addresses (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  country_id uuid REFERENCES directory.countries(id),
  administrative_area_id uuid REFERENCES directory.administrative_areas(id),
  city_id uuid REFERENCES directory.cities(id),
  district_id uuid REFERENCES directory.districts(id),
  neighbourhood_id uuid REFERENCES directory.neighbourhoods(id),
  address_line1 text,
  address_line2 text,
  landmark text,
  postal_code text,
  location geography(Point,4326),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- DIRECTORY
CREATE TABLE verification.verification_levels (
  id smallserial PRIMARY KEY,
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  rank smallint UNIQUE NOT NULL,
  description text
);

CREATE TABLE directory.businesses (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  public_id text UNIQUE NOT NULL,
  legal_name text NOT NULL,
  trading_name text NOT NULL,
  slug citext UNIQUE NOT NULL,
  description text,
  short_description text,
  registration_number text,
  tax_number text,
  established_year smallint,
  status text NOT NULL DEFAULT 'draft'
    CHECK (status IN ('draft','pending','published','suspended','closed')),
  verification_level_id smallint REFERENCES verification.verification_levels(id),
  primary_city_id uuid REFERENCES directory.cities(id),
  primary_address_id uuid REFERENCES directory.addresses(id),
  logo_url text,
  cover_url text,
  website_url text,
  profile_completeness smallint NOT NULL DEFAULT 0 CHECK (profile_completeness BETWEEN 0 AND 100),
  average_rating numeric(3,2) NOT NULL DEFAULT 0,
  review_count integer NOT NULL DEFAULT 0,
  published_at timestamptz,
  created_by uuid REFERENCES iam.users(id),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  deleted_at timestamptz
);

CREATE TABLE directory.business_members (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  user_id uuid NOT NULL REFERENCES iam.users(id) ON DELETE CASCADE,
  role_code text NOT NULL,
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('invited','active','suspended','removed')),
  invited_by uuid REFERENCES iam.users(id),
  joined_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(business_id, user_id)
);

CREATE TABLE directory.business_claims (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  claimant_user_id uuid NOT NULL REFERENCES iam.users(id),
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected','cancelled')),
  evidence_summary text,
  reviewed_by uuid REFERENCES iam.users(id),
  reviewed_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE directory.business_branches (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  name text NOT NULL,
  slug citext NOT NULL,
  address_id uuid REFERENCES directory.addresses(id),
  phone text,
  email citext,
  manager_user_id uuid REFERENCES iam.users(id),
  is_head_office boolean NOT NULL DEFAULT false,
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('active','inactive','closed')),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(business_id, slug)
);

CREATE TABLE directory.business_contacts (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  branch_id uuid REFERENCES directory.business_branches(id) ON DELETE CASCADE,
  contact_type text NOT NULL CHECK (contact_type IN ('phone','whatsapp','email','website','fax')),
  label text,
  value text NOT NULL,
  is_primary boolean NOT NULL DEFAULT false,
  is_public boolean NOT NULL DEFAULT true,
  verified_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE directory.business_social_links (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  platform text NOT NULL,
  url text NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(business_id, platform)
);

CREATE TABLE directory.business_opening_hours (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  branch_id uuid NOT NULL REFERENCES directory.business_branches(id) ON DELETE CASCADE,
  weekday smallint NOT NULL CHECK (weekday BETWEEN 0 AND 6),
  opens_at time,
  closes_at time,
  is_closed boolean NOT NULL DEFAULT false,
  UNIQUE(branch_id, weekday)
);

CREATE TABLE directory.business_special_hours (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  branch_id uuid NOT NULL REFERENCES directory.business_branches(id) ON DELETE CASCADE,
  service_date date NOT NULL,
  opens_at time,
  closes_at time,
  is_closed boolean NOT NULL DEFAULT false,
  note text,
  UNIQUE(branch_id, service_date)
);

CREATE TABLE directory.categories (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  parent_id uuid REFERENCES directory.categories(id),
  name text NOT NULL,
  slug citext UNIQUE NOT NULL,
  description text,
  icon text,
  active boolean NOT NULL DEFAULT true,
  sort_order integer NOT NULL DEFAULT 0,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE directory.category_closure (
  ancestor_id uuid REFERENCES directory.categories(id) ON DELETE CASCADE,
  descendant_id uuid REFERENCES directory.categories(id) ON DELETE CASCADE,
  depth integer NOT NULL CHECK (depth >= 0),
  PRIMARY KEY(ancestor_id, descendant_id)
);

CREATE TABLE directory.business_categories (
  business_id uuid REFERENCES directory.businesses(id) ON DELETE CASCADE,
  category_id uuid REFERENCES directory.categories(id),
  is_primary boolean NOT NULL DEFAULT false,
  created_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY(business_id, category_id)
);

CREATE TABLE directory.services (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  category_id uuid REFERENCES directory.categories(id),
  name text NOT NULL,
  slug citext UNIQUE NOT NULL,
  description text,
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE directory.business_services (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  service_id uuid REFERENCES directory.services(id),
  custom_name text,
  description text,
  price_from numeric(14,2),
  currency char(3),
  active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE directory.business_media (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  branch_id uuid REFERENCES directory.business_branches(id) ON DELETE CASCADE,
  media_type text NOT NULL CHECK (media_type IN ('image','video','document')),
  storage_key text NOT NULL,
  caption text,
  alt_text text,
  sort_order integer NOT NULL DEFAULT 0,
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('processing','active','rejected','deleted')),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE directory.business_keywords (
  business_id uuid REFERENCES directory.businesses(id) ON DELETE CASCADE,
  keyword citext NOT NULL,
  PRIMARY KEY(business_id, keyword)
);

CREATE TABLE directory.business_service_areas (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id) ON DELETE CASCADE,
  city_id uuid REFERENCES directory.cities(id),
  district_id uuid REFERENCES directory.districts(id),
  radius_km numeric(8,2),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- VERIFICATION
CREATE TABLE verification.verification_requests (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  requested_level_id smallint NOT NULL REFERENCES verification.verification_levels(id),
  status text NOT NULL DEFAULT 'submitted'
    CHECK (status IN ('submitted','under_review','information_requested','approved','rejected','cancelled')),
  submitted_by uuid NOT NULL REFERENCES iam.users(id),
  assigned_to uuid REFERENCES iam.users(id),
  submitted_at timestamptz NOT NULL DEFAULT now(),
  decided_at timestamptz
);

CREATE TABLE verification.verification_checks (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id uuid NOT NULL REFERENCES verification.verification_requests(id) ON DELETE CASCADE,
  check_type text NOT NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','passed','failed','not_applicable')),
  checked_by uuid REFERENCES iam.users(id),
  checked_at timestamptz,
  notes text,
  UNIQUE(request_id, check_type)
);

CREATE TABLE verification.verification_documents (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id uuid NOT NULL REFERENCES verification.verification_requests(id) ON DELETE CASCADE,
  document_type text NOT NULL,
  storage_key text NOT NULL,
  document_number text,
  issued_at date,
  expires_at date,
  status text NOT NULL DEFAULT 'submitted' CHECK (status IN ('submitted','accepted','rejected')),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE verification.verification_visits (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id uuid NOT NULL REFERENCES verification.verification_requests(id) ON DELETE CASCADE,
  verifier_user_id uuid REFERENCES iam.users(id),
  scheduled_at timestamptz,
  completed_at timestamptz,
  location geography(Point,4326),
  outcome text,
  notes text
);

CREATE TABLE verification.verification_decisions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  request_id uuid UNIQUE NOT NULL REFERENCES verification.verification_requests(id) ON DELETE CASCADE,
  decision text NOT NULL CHECK (decision IN ('approved','rejected')),
  approved_level_id smallint REFERENCES verification.verification_levels(id),
  reason text,
  decided_by uuid NOT NULL REFERENCES iam.users(id),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- REVIEWS
CREATE TABLE reviews.reviews (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  user_id uuid NOT NULL REFERENCES iam.users(id),
  rating smallint NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title text,
  body text,
  status text NOT NULL DEFAULT 'pending'
    CHECK (status IN ('pending','approved','rejected','hidden')),
  verified_customer boolean NOT NULL DEFAULT false,
  published_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE reviews.review_responses (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  review_id uuid UNIQUE NOT NULL REFERENCES reviews.reviews(id) ON DELETE CASCADE,
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  author_user_id uuid NOT NULL REFERENCES iam.users(id),
  body text NOT NULL,
  status text NOT NULL DEFAULT 'published' CHECK (status IN ('published','hidden')),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE reviews.review_votes (
  review_id uuid REFERENCES reviews.reviews(id) ON DELETE CASCADE,
  user_id uuid REFERENCES iam.users(id) ON DELETE CASCADE,
  vote text NOT NULL CHECK (vote IN ('helpful','not_helpful')),
  created_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY(review_id, user_id)
);

CREATE TABLE reviews.review_reports (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  review_id uuid NOT NULL REFERENCES reviews.reviews(id) ON DELETE CASCADE,
  reported_by uuid NOT NULL REFERENCES iam.users(id),
  reason text NOT NULL,
  status text NOT NULL DEFAULT 'open' CHECK (status IN ('open','resolved','dismissed')),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- LEADS
CREATE TABLE leads.leads (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  reference_no text UNIQUE NOT NULL,
  created_by_user_id uuid REFERENCES iam.users(id),
  category_id uuid REFERENCES directory.categories(id),
  city_id uuid REFERENCES directory.cities(id),
  title text NOT NULL,
  description text,
  customer_name text NOT NULL,
  customer_phone text,
  customer_email citext,
  budget_min numeric(14,2),
  budget_max numeric(14,2),
  currency char(3),
  status text NOT NULL DEFAULT 'open' CHECK (status IN ('draft','open','matched','closed','cancelled')),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE leads.lead_service_items (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  lead_id uuid NOT NULL REFERENCES leads.leads(id) ON DELETE CASCADE,
  service_id uuid REFERENCES directory.services(id),
  custom_service_name text,
  quantity numeric(14,3),
  notes text
);

CREATE TABLE leads.lead_recipients (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  lead_id uuid NOT NULL REFERENCES leads.leads(id) ON DELETE CASCADE,
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  status text NOT NULL DEFAULT 'new'
    CHECK (status IN ('new','viewed','contacted','qualified','proposal_sent','won','lost','declined')),
  received_at timestamptz NOT NULL DEFAULT now(),
  viewed_at timestamptz,
  responded_at timestamptz,
  UNIQUE(lead_id, business_id)
);

CREATE TABLE leads.lead_assignments (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  lead_recipient_id uuid NOT NULL REFERENCES leads.lead_recipients(id) ON DELETE CASCADE,
  assigned_to_user_id uuid NOT NULL REFERENCES iam.users(id),
  assigned_by_user_id uuid REFERENCES iam.users(id),
  assigned_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE leads.lead_messages (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  lead_recipient_id uuid NOT NULL REFERENCES leads.lead_recipients(id) ON DELETE CASCADE,
  sender_user_id uuid REFERENCES iam.users(id),
  sender_type text NOT NULL CHECK (sender_type IN ('customer','business','system')),
  body text NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE leads.lead_status_history (
  id bigserial PRIMARY KEY,
  lead_recipient_id uuid NOT NULL REFERENCES leads.lead_recipients(id) ON DELETE CASCADE,
  old_status text,
  new_status text NOT NULL,
  changed_by uuid REFERENCES iam.users(id),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- BILLING
CREATE TABLE billing.plans (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  billing_interval text NOT NULL CHECK (billing_interval IN ('monthly','quarterly','yearly','one_time')),
  price numeric(14,2) NOT NULL DEFAULT 0,
  currency char(3) NOT NULL DEFAULT 'USD',
  active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE billing.plan_features (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  value_type text NOT NULL CHECK (value_type IN ('boolean','integer','decimal','text'))
);

CREATE TABLE billing.plan_feature_values (
  plan_id uuid REFERENCES billing.plans(id) ON DELETE CASCADE,
  feature_id uuid REFERENCES billing.plan_features(id) ON DELETE CASCADE,
  value_text text,
  PRIMARY KEY(plan_id, feature_id)
);

CREATE TABLE billing.subscriptions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  plan_id uuid NOT NULL REFERENCES billing.plans(id),
  status text NOT NULL DEFAULT 'active'
    CHECK (status IN ('trial','active','past_due','paused','cancelled','expired')),
  starts_at timestamptz NOT NULL,
  ends_at timestamptz,
  auto_renew boolean NOT NULL DEFAULT true,
  cancelled_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE billing.subscription_history (
  id bigserial PRIMARY KEY,
  subscription_id uuid NOT NULL REFERENCES billing.subscriptions(id) ON DELETE CASCADE,
  event_type text NOT NULL,
  old_plan_id uuid REFERENCES billing.plans(id),
  new_plan_id uuid REFERENCES billing.plans(id),
  metadata jsonb NOT NULL DEFAULT '{}',
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE billing.invoices (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_no text UNIQUE NOT NULL,
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  subscription_id uuid REFERENCES billing.subscriptions(id),
  status text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','issued','partially_paid','paid','void','overdue')),
  currency char(3) NOT NULL DEFAULT 'USD',
  subtotal numeric(14,2) NOT NULL DEFAULT 0,
  tax_total numeric(14,2) NOT NULL DEFAULT 0,
  total numeric(14,2) NOT NULL DEFAULT 0,
  amount_paid numeric(14,2) NOT NULL DEFAULT 0,
  issued_at timestamptz,
  due_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE billing.invoice_items (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_id uuid NOT NULL REFERENCES billing.invoices(id) ON DELETE CASCADE,
  description text NOT NULL,
  quantity numeric(14,3) NOT NULL DEFAULT 1,
  unit_price numeric(14,2) NOT NULL,
  line_total numeric(14,2) NOT NULL
);

CREATE TABLE billing.payment_providers (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  active boolean NOT NULL DEFAULT true,
  configuration jsonb NOT NULL DEFAULT '{}'
);

CREATE TABLE billing.payments (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_id uuid REFERENCES billing.invoices(id),
  provider_id uuid NOT NULL REFERENCES billing.payment_providers(id),
  provider_reference text,
  status text NOT NULL CHECK (status IN ('pending','authorized','paid','failed','cancelled','refunded')),
  amount numeric(14,2) NOT NULL,
  currency char(3) NOT NULL,
  paid_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(provider_id, provider_reference)
);

CREATE TABLE billing.refunds (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  payment_id uuid NOT NULL REFERENCES billing.payments(id),
  amount numeric(14,2) NOT NULL,
  reason text,
  status text NOT NULL CHECK (status IN ('pending','processed','failed')),
  provider_reference text,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- ADVERTISING
CREATE TABLE advertising.ad_placements (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  name text NOT NULL,
  placement_type text NOT NULL,
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE advertising.ad_campaigns (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  name text NOT NULL,
  status text NOT NULL DEFAULT 'draft'
    CHECK (status IN ('draft','pending','approved','running','paused','completed','rejected')),
  starts_at timestamptz,
  ends_at timestamptz,
  total_budget numeric(14,2),
  daily_budget numeric(14,2),
  currency char(3) NOT NULL DEFAULT 'USD',
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE advertising.ad_creatives (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  campaign_id uuid NOT NULL REFERENCES advertising.ad_campaigns(id) ON DELETE CASCADE,
  placement_id uuid NOT NULL REFERENCES advertising.ad_placements(id),
  headline text,
  body text,
  image_url text,
  destination_url text,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE advertising.ad_target_locations (
  campaign_id uuid REFERENCES advertising.ad_campaigns(id) ON DELETE CASCADE,
  city_id uuid REFERENCES directory.cities(id),
  district_id uuid REFERENCES directory.districts(id),
  PRIMARY KEY(campaign_id, city_id, district_id)
);

CREATE TABLE advertising.ad_target_categories (
  campaign_id uuid REFERENCES advertising.ad_campaigns(id) ON DELETE CASCADE,
  category_id uuid REFERENCES directory.categories(id),
  PRIMARY KEY(campaign_id, category_id)
);

CREATE TABLE advertising.ad_events (
  id bigserial PRIMARY KEY,
  campaign_id uuid NOT NULL REFERENCES advertising.ad_campaigns(id),
  creative_id uuid REFERENCES advertising.ad_creatives(id),
  event_type text NOT NULL CHECK (event_type IN ('impression','click','lead')),
  user_id uuid REFERENCES iam.users(id),
  occurred_at timestamptz NOT NULL DEFAULT now(),
  metadata jsonb NOT NULL DEFAULT '{}'
);

-- NOTIFICATIONS
CREATE TABLE notifications.notification_templates (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  code text UNIQUE NOT NULL,
  channel text NOT NULL CHECK (channel IN ('email','sms','whatsapp','push','in_app')),
  subject_template text,
  body_template text NOT NULL,
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE notifications.notifications (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES iam.users(id) ON DELETE CASCADE,
  template_id uuid REFERENCES notifications.notification_templates(id),
  title text NOT NULL,
  body text NOT NULL,
  data jsonb NOT NULL DEFAULT '{}',
  read_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE notifications.notification_deliveries (
  id bigserial PRIMARY KEY,
  notification_id uuid REFERENCES notifications.notifications(id) ON DELETE CASCADE,
  channel text NOT NULL,
  destination text,
  status text NOT NULL CHECK (status IN ('queued','sent','delivered','failed')),
  provider_reference text,
  attempted_at timestamptz NOT NULL DEFAULT now(),
  error_message text
);

-- ANALYTICS
CREATE TABLE analytics.search_events (
  id bigserial PRIMARY KEY,
  user_id uuid REFERENCES iam.users(id),
  query text,
  city_id uuid REFERENCES directory.cities(id),
  category_id uuid REFERENCES directory.categories(id),
  result_count integer,
  occurred_at timestamptz NOT NULL DEFAULT now(),
  metadata jsonb NOT NULL DEFAULT '{}'
);

CREATE TABLE analytics.business_events (
  id bigserial PRIMARY KEY,
  business_id uuid NOT NULL REFERENCES directory.businesses(id),
  user_id uuid REFERENCES iam.users(id),
  event_type text NOT NULL,
  occurred_at timestamptz NOT NULL DEFAULT now(),
  metadata jsonb NOT NULL DEFAULT '{}'
);

CREATE TABLE analytics.daily_business_metrics (
  business_id uuid REFERENCES directory.businesses(id) ON DELETE CASCADE,
  metric_date date NOT NULL,
  impressions integer NOT NULL DEFAULT 0,
  profile_views integer NOT NULL DEFAULT 0,
  phone_clicks integer NOT NULL DEFAULT 0,
  whatsapp_clicks integer NOT NULL DEFAULT 0,
  website_clicks integer NOT NULL DEFAULT 0,
  direction_clicks integer NOT NULL DEFAULT 0,
  quote_requests integer NOT NULL DEFAULT 0,
  PRIMARY KEY(business_id, metric_date)
);

-- MODERATION
CREATE TABLE moderation.reports (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  reporter_user_id uuid REFERENCES iam.users(id),
  entity_type text NOT NULL,
  entity_id uuid NOT NULL,
  reason_code text NOT NULL,
  description text,
  status text NOT NULL DEFAULT 'open' CHECK (status IN ('open','investigating','resolved','dismissed')),
  assigned_to uuid REFERENCES iam.users(id),
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE moderation.moderation_actions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  report_id uuid REFERENCES moderation.reports(id),
  actor_user_id uuid NOT NULL REFERENCES iam.users(id),
  action_type text NOT NULL,
  entity_type text NOT NULL,
  entity_id uuid NOT NULL,
  reason text,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE moderation.fraud_signals (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  entity_type text NOT NULL,
  entity_id uuid NOT NULL,
  signal_type text NOT NULL,
  risk_score numeric(5,2) NOT NULL CHECK (risk_score BETWEEN 0 AND 100),
  status text NOT NULL DEFAULT 'open' CHECK (status IN ('open','reviewed','confirmed','dismissed')),
  evidence jsonb NOT NULL DEFAULT '{}',
  created_at timestamptz NOT NULL DEFAULT now()
);

-- CMS
CREATE TABLE cms.pages (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  slug citext UNIQUE NOT NULL,
  title text NOT NULL,
  body jsonb NOT NULL DEFAULT '{}',
  status text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','published','archived')),
  seo_title text,
  seo_description text,
  published_at timestamptz,
  created_by uuid REFERENCES iam.users(id),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE cms.articles (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  slug citext UNIQUE NOT NULL,
  title text NOT NULL,
  excerpt text,
  body jsonb NOT NULL DEFAULT '{}',
  status text NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','published','archived')),
  author_user_id uuid REFERENCES iam.users(id),
  published_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE cms.faqs (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  question text NOT NULL,
  answer text NOT NULL,
  sort_order integer NOT NULL DEFAULT 0,
  active boolean NOT NULL DEFAULT true
);

CREATE TABLE cms.banners (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  placement_code text NOT NULL,
  title text,
  image_url text,
  destination_url text,
  starts_at timestamptz,
  ends_at timestamptz,
  active boolean NOT NULL DEFAULT true
);

-- SYSTEM
CREATE TABLE system.audit_logs (
  id bigserial PRIMARY KEY,
  actor_user_id uuid REFERENCES iam.users(id),
  action text NOT NULL,
  entity_type text,
  entity_id uuid,
  ip_address inet,
  user_agent text,
  before_data jsonb,
  after_data jsonb,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE system.system_settings (
  key text PRIMARY KEY,
  value jsonb NOT NULL,
  is_secret boolean NOT NULL DEFAULT false,
  updated_by uuid REFERENCES iam.users(id),
  updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE system.webhooks (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  business_id uuid REFERENCES directory.businesses(id),
  url text NOT NULL,
  secret_hash text NOT NULL,
  subscribed_events text[] NOT NULL DEFAULT '{}',
  active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE system.webhook_deliveries (
  id bigserial PRIMARY KEY,
  webhook_id uuid NOT NULL REFERENCES system.webhooks(id) ON DELETE CASCADE,
  event_type text NOT NULL,
  payload jsonb NOT NULL,
  status text NOT NULL CHECK (status IN ('queued','delivered','failed')),
  response_code integer,
  attempted_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE system.idempotency_keys (
  idempotency_key text PRIMARY KEY,
  user_id uuid REFERENCES iam.users(id),
  request_hash text NOT NULL,
  response_status integer,
  response_body jsonb,
  expires_at timestamptz NOT NULL,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- INDEXES
CREATE INDEX idx_businesses_status_city_verify
  ON directory.businesses(status, primary_city_id, verification_level_id);

CREATE INDEX idx_businesses_name_trgm
  ON directory.businesses USING gin (trading_name gin_trgm_ops);

CREATE INDEX idx_business_categories_category
  ON directory.business_categories(category_id, business_id);

CREATE INDEX idx_branch_business_status
  ON directory.business_branches(business_id, status);

CREATE INDEX idx_address_location
  ON directory.addresses USING gist(location);

CREATE INDEX idx_reviews_business_status_date
  ON reviews.reviews(business_id, status, created_at DESC);

CREATE INDEX idx_leads_creator_date
  ON leads.leads(created_by_user_id, created_at DESC);

CREATE INDEX idx_lead_recipients_business_status_date
  ON leads.lead_recipients(business_id, status, received_at DESC);

CREATE INDEX idx_subscriptions_business_status_end
  ON billing.subscriptions(business_id, status, ends_at);

CREATE INDEX idx_payments_provider_reference
  ON billing.payments(provider_id, provider_reference);

CREATE INDEX idx_ad_events_campaign_date
  ON advertising.ad_events(campaign_id, occurred_at);

CREATE INDEX idx_search_events_date
  ON analytics.search_events(occurred_at);

CREATE INDEX idx_business_events_business_date
  ON analytics.business_events(business_id, occurred_at DESC);

CREATE INDEX idx_audit_actor_date
  ON system.audit_logs(actor_user_id, created_at DESC);

-- UPDATED_AT TRIGGERS
CREATE TRIGGER trg_users_updated
BEFORE UPDATE ON iam.users
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_profiles_updated
BEFORE UPDATE ON iam.user_profiles
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_businesses_updated
BEFORE UPDATE ON directory.businesses
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_branches_updated
BEFORE UPDATE ON directory.business_branches
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_business_services_updated
BEFORE UPDATE ON directory.business_services
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_reviews_updated
BEFORE UPDATE ON reviews.reviews
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_review_responses_updated
BEFORE UPDATE ON reviews.review_responses
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_leads_updated
BEFORE UPDATE ON leads.leads
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_campaigns_updated
BEFORE UPDATE ON advertising.ad_campaigns
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_pages_updated
BEFORE UPDATE ON cms.pages
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();

CREATE TRIGGER trg_articles_updated
BEFORE UPDATE ON cms.articles
FOR EACH ROW EXECUTE FUNCTION system.set_updated_at();
