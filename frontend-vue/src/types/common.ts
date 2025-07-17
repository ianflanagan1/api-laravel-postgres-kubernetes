export interface ApiResponseSuccess<T> {
  data: T
  meta: ResponseMetaSection
}

export interface ApiResponsePaginated<T> {
  data: T[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
  meta: ResponseMetaSection
}

export interface ApiResponseError {
  errors: Array<ApiError>
  meta: ResponseMetaSection
}

export interface ApiError {
  code: number
  message: string
}

interface ResponseMetaSection {
  timestamp: string // ISO 8601 string
  request_id: string // UUID
  duration: number
}
