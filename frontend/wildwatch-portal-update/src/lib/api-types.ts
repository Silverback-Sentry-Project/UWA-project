// Shape returned by Laravel's ->paginate() calls across the admin API.
export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}
