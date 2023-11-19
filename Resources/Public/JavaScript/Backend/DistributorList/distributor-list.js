;(function () {
  const handleBulkActions = (form) => {
    const bulkActions = Array.from(
      form.querySelectorAll('[data-action-type="bulk"]')
    )
    const list = Array.from(
      form.querySelectorAll('.t3js-multi-record-selection-check')
    )
    if (list.length < 2) {
      return
    }
    const toggle = list.shift()
    const anyChecked = () => {
      for (let index = 0; index < list.length; index++) {
        if (list[index].checked) {
          return true
        }
      }
      return false
    }
    const allChecked = () => {
      for (let index = 0; index < list.length; index++) {
        if (!list[index].checked) {
          return false
        }
      }
      return true
    }
    const updateBulkActions = () => {
      const disabled = !anyChecked()
      bulkActions.forEach((bulkAction) => {
        bulkAction.disabled = disabled
      })
    }
    const checkAll = (checked) => {
      list.forEach((checkBox) => {
        checkBox.checked = checked
      })
    }
    toggle.addEventListener('change', function () {
      if (allChecked()) {
        checkAll(false)
      } else {
        checkAll(true)
      }
      updateBulkActions()
    })
    list.forEach((checkbox) => {
      checkbox.addEventListener('change', function () {
        toggle.checked = allChecked()
        updateBulkActions()
      })
    })
  }

  const handleSortingUi = (form) => {
    function update(list) {
      list.querySelectorAll('[data-move]').forEach((button) => {
        button.style.display = ''
      })
      const items = list.querySelectorAll('[data-movable-item]')
      for (let i = 0; i < items.length; i++) {
        const item = list.querySelector('[data-movable-item="' + i + '"]')
        list.appendChild(item)
        if (i === 0) {
          item.querySelector('[data-move="up"]').style.display = 'none'
        }
        if (i === items.length - 1) {
          item.querySelector('[data-move="down"]').style.display = 'none'
        }
      }
    }
    function move(list, item, direction) {
      const index = parseInt(item.dataset.movableItem)
      const switchItem = list.querySelector(
        '[data-movable-item="' + (index + direction) + '"]'
      )
      item.dataset.movableItem = index + direction
      switchItem.dataset.movableItem = index
      update(list)
    }
    function handleMovableItem(list, item) {
      const up = item.querySelector('[data-move="up"]')
      up.addEventListener('click', (e) => {
        move(list, item, -1)
      })
      const down = item.querySelector('[data-move="down"]')
      down.addEventListener('click', (e) => {
        move(list, item, 1)
      })
    }
    function handleMovableList(list) {
      const items = list.querySelectorAll('[data-movable-item]')
      items.forEach((item) => {
        handleMovableItem(list, item)
      })
      update(list)
    }
    form.querySelectorAll('[data-movable-list]').forEach((movableList) => {
      handleMovableList(movableList)
    })
  }

  const handleConfirmElements = (form) => {
    const handleConfirm = (toBeConfirmed) => {
      const message = toBeConfirmed.dataset.confirm
      toBeConfirmed.addEventListener('click', (e) => {
        if (!confirm(message)) {
          e.preventDefault()
        }
      })
    }
    form.querySelectorAll('[data-confirm]').forEach((toBeConfirmed) => {
      handleConfirm(toBeConfirmed)
    })
  }

  document.addEventListener('DOMContentLoaded', () => {
    document
      .querySelectorAll(
        '[data-module="dmf-distributor-backend"][data-form-type]'
      )
      .forEach((form) => {
        switch (form.dataset.formType) {
          case 'showStatistics':
            handleConfirmElements(form)
            break
          case 'showErrors':
            handleConfirmElements(form)
            handleSortingUi(form)
            break
          case 'list':
            handleConfirmElements(form)
            handleSortingUi(form)
            handleBulkActions(form)
            break
        }
      })
  })
})()
