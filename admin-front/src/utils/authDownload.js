const DEFAULT_DOWNLOAD_ERROR = 'No se pudo descargar el archivo. Intente nuevamente.'

function buildApiUrl(endpoint) {
  const normalizedBase = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/$/, '')
  const normalizedEndpoint = endpoint.replace(/^\//, '')

  return `${normalizedBase}/${normalizedEndpoint}`
}

function buildQueryString(query = {}) {
  const params = new URLSearchParams()

  Object.entries(query).forEach(([key, value]) => {
    if (value === null || value === undefined || value === '') {
      return
    }

    params.append(key, String(value).trim())
  })

  const serialized = params.toString()

  return serialized ? `?${serialized}` : ''
}

function parseFilenameFromDisposition(dispositionHeader) {
  if (!dispositionHeader) {
    return null
  }

  const utf8Match = dispositionHeader.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1])
  }

  const asciiMatch = dispositionHeader.match(/filename="?([^";]+)"?/i)
  if (asciiMatch?.[1]) {
    return asciiMatch[1]
  }

  return null
}

function triggerBrowserDownload(blob, filename) {
  const blobUrl = window.URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = blobUrl
  anchor.download = filename
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()

  setTimeout(() => {
    window.URL.revokeObjectURL(blobUrl)
  }, 250)
}

export async function downloadAuthenticatedFile(endpoint, options = {}) {
  const {
    method = 'GET',
    query = {},
    filename = 'archivo',
    headers = {},
    errorMessage = DEFAULT_DOWNLOAD_ERROR,
  } = options

  const token = localStorage.getItem('token')
  const url = `${buildApiUrl(endpoint)}${buildQueryString(query)}`
  const response = await fetch(url, {
    method,
    headers: {
      ...headers,
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  })

  if (!response.ok) {
    let backendMessage = ''
    try {
      const payload = await response.json()
      backendMessage = payload?.message || payload?.error || ''
    } catch {
      backendMessage = ''
    }

    throw new Error(backendMessage || errorMessage)
  }

  const blob = await response.blob()
  const disposition = response.headers.get('content-disposition')
  const resolvedFilename = parseFilenameFromDisposition(disposition) || filename

  triggerBrowserDownload(blob, resolvedFilename)
}
