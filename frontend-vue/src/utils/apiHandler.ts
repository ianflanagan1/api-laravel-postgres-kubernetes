import { isAxiosError } from 'axios'
import { useToast } from 'vue-toastification'
import { type ApiResponseError } from '@/types/common'

const toast = useToast()

export interface ErrorState {
  error: string | null
  showError: boolean
}
export interface PageState {
  isLoading: boolean
}

export const handleApi = async (
  apiCall: () => Promise<any>,
  errorState?: ErrorState,
  pageState?: PageState,
): Promise<void> => {
  if (errorState) {
    errorState.showError = false
    errorState.error = ''
  }
  if (pageState) {
    pageState.isLoading = true
  }

  try {
    await apiCall()
  } catch (error: unknown) {
    if (isAxiosError(error)) {
      if (error.response?.data?.errors !== undefined) {
        const errorResponse: ApiResponseError = error.response.data
        const message = errorResponse.errors.map((err) => err.message).join('. ')

        if (errorState) {
          errorState.error = message
          errorState.showError = true
        } else {
          toast.error(message)
        }
      } else {
        toast.error('Server error')
      }
    } else {
      toast.error('Network error')
    }
  } finally {
    if (pageState) {
      pageState.isLoading = false
    }
  }
}
