export type DocumentStatus = 'uploaded' | 'processing' | 'processed' | 'failed'

export interface Document {
  id: string
  original_filename: string
  mime_type: string
  size_bytes: number
  status: DocumentStatus
  document_type: string | null
  title: string | null
  summary: string | null
  processing_error: string | null
  processed_at: string | null
  created_at: string
  // Detail fields (only on GET /documents/:id)
  extraction_version?: string | null
  counterparty_name?: string | null
  contract_holder_name?: string | null
  contract_number?: string | null
  policy_number?: string | null
  start_date?: string | null
  end_date?: string | null
  duration_text?: string | null
  cancellation_period?: string | null
  payment_amount?: number | null
  payment_currency?: string | null
  payment_interval?: string | null
  important_terms?: string | null
  exclusions?: string | null
  contact_details?: string | null
  custom_fields_json?: Record<string, unknown>
}
