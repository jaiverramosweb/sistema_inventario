import { ofetch } from 'ofetch'

const DEFAULT_API_ERROR_MESSAGE = 'No se pudo completar la solicitud. Intente nuevamente.'

function extractValidationMessage(errors) {
  if (!errors) {
    return null
  }

  if (Array.isArray(errors)) {
    return errors.find(item => typeof item === 'string' && item.trim()) || null
  }

  if (typeof errors === 'object') {
    for (const value of Object.values(errors)) {
      if (Array.isArray(value)) {
        const firstMessage = value.find(item => typeof item === 'string' && item.trim())
        if (firstMessage) {
          return firstMessage
        }
      }

      if (typeof value === 'string' && value.trim()) {
        return value
      }
    }
  }

  return null
}

function resolveApiErrorMessage(response) {
  const payload = response?._data

  if (!payload) {
    return null
  }

  if (typeof payload === 'string' && payload.trim()) {
    return payload
  }

  const candidates = [
    payload.error,
    payload.message,
    payload.detail,
    extractValidationMessage(payload.errors),
  ]

  return candidates.find(item => typeof item === 'string' && item.trim()) || null
}

function parseJwt(token) {
  try {
    // Aquí separamos la segunda parte (el payload) que contiene los datos como la fecha de expiración
    const base64Url = token.split('.')[1] // Obtenemos el payload que está en formato base64Url
    
    // El formato base64Url usa '-' y '_' en lugar de '+' y '/' respectivamente
    // Necesitamos reemplazarlos para que sea decodificable en base64
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
    
    // Decodificamos la cadena base64
    // atob() convierte la cadena base64 a texto legible
    // Luego usamos decodeURIComponent para manejar correctamente los caracteres especiales
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2) // Convertimos a formato URI
    }).join(''))
    
    // Finalmente convertimos el payload decodificado a un objeto JSON y lo retornamos
    return JSON.parse(jsonPayload)
  } catch (e) {
    return null
  }
}

function isTokenExpired(token) {
  const decodedToken = parseJwt(token)
  if (!decodedToken || !decodedToken.exp) {
    return true // Token inválido o sin fecha de expiración
  }
  const currentTime = Math.floor(Date.now() / 1000) // Tiempo actual en segundos
  return decodedToken.exp < currentTime // Retorna true si el token ha expirado
}


export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest( response ) {
    const accessToken = localStorage.getItem("token") //useCookie('accessToken').value
    if(accessToken && isTokenExpired(accessToken) && response.request !=  `auth/login`){
      localStorage.removeItem("token")
      localStorage.removeItem("user")

      setTimeout(() => {
        window.location.reload()
      }, 100)
    }
    if (accessToken) {
      const { options } = response

      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${accessToken}`,
      }
    }
  },
  onResponseError({ response }) {
    const normalizedMessage = resolveApiErrorMessage(response) || DEFAULT_API_ERROR_MESSAGE
    const currentPayload = response?._data

    if (!currentPayload || typeof currentPayload !== 'object' || Array.isArray(currentPayload)) {
      response._data = {
        error: normalizedMessage,
        message: normalizedMessage,
      }

      return
    }

    if (!currentPayload.error) {
      currentPayload.error = normalizedMessage
    }

    if (!currentPayload.message) {
      currentPayload.message = normalizedMessage
    }
  },
})
