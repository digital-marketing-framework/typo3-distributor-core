;(function () {
  const textarea = document.querySelector('.dmf-configuration-document')
  if (textarea === null) {
    return
  }
  const appScriptUrl = textarea.dataset.appScript
  const appStylesUrl = textarea.dataset.appStyles

  const script = document.createElement('script')
  script.src = '/' + appScriptUrl
  document.body.appendChild(script)

  const style = document.createElement('link')
  style.rel = 'stylesheet'
  style.media = 'all'
  style.href = '/' + appStylesUrl
  document.head.appendChild(style)
})()
