let configData = null
let selectedDisks = new Set()
let diskList = ['C', 'D', 'E'];
const fileInput = document.getElementById('file-input')
const configDisplay = document.getElementById('config-display')
const noConfig = document.getElementById('no-config')
const diskListEl = document.getElementById('disk-list')
const messageArea = document.getElementById('message-area')
const selectedInfo = document.getElementById('selected-info')
const selectedCount = document.getElementById('selected-count')
const freezeBtn = document.getElementById('freeze-btn')
const unfreezeBtn = document.getElementById('unfreeze-btn')
const selectAllBtn = document.getElementById('select-all-btn')
const clearSelectionBtn = document.getElementById('clear-selection-btn')
const reloadConfigBtn = document.getElementById('reload-config')
const addDiskBtn = document.getElementById('add-disk-btn')
const apiV0Mode = document.getElementById('api-v0-mode')
const diskCountInfo = document.getElementById('disk-count-info')
const fileGuideModal = document.getElementById('file-guide-modal')
const openFilePickerBtn = document.getElementById('open-file-picker')
const closeModalBtn = document.getElementById('close-modal')
const drawer = document.getElementById('drawer')
const drawerOverlay = document.getElementById('drawer-overlay')
const menuToggle = document.getElementById('menu-toggle');
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => { showFileGuideModal() }, 500);
    fileInput.addEventListener('change', handleFileSelect);
    reloadConfigBtn.addEventListener('click', showFileGuideModal);
    freezeBtn.addEventListener('click', () => handleDiskAction('freeze'));
    unfreezeBtn.addEventListener('click', () => handleDiskAction('unfreeze'));
    selectAllBtn.addEventListener('click', selectAllDisks);
    clearSelectionBtn.addEventListener('click', clearSelection);
    addDiskBtn.addEventListener('click', addNewDisk);
    apiV0Mode.addEventListener('change', toggleApiMode);
    openFilePickerBtn.addEventListener('click', openFilePicker);
    closeModalBtn.addEventListener('click', hideFileGuideModal);
    menuToggle.addEventListener('click', toggleDrawer);
    drawerOverlay.addEventListener('click', hideDrawer);
    renderDiskList();
    updateDiskCount()
});
function showFileGuideModal() {
    fileGuideModal.style.display = 'flex';
    copyToClipboard('C:\\ProgramData\\SeewoFreezeKernelConfig\\');
    fileInput.value = '';
    setTimeout(openFilePicker, 100);
}
function hideFileGuideModal() {
    fileGuideModal.style.display = 'none'
}
function openFilePicker() {
    fileInput.click()
}
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).catch(() => { })
    }
}
function toggleDrawer() {
    drawerOverlay.classList.add('active')
    let clearDrawer = setInterval(() => {
        if (!drawer.classList.contains('is-visible')) {
            drawerOverlay.classList.remove('active');
            clearInterval(clearDrawer);
        }
    }, 10);
}

async function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.name !== 'VolumeInfo.config') {
        showMessage('请选择名为 "VolumeInfo.config" 的配置文件', 'error');
        return
    } try {
        const byteArray = await processConfigFile(file);
        configData = parseConfigData(byteArray);
        displayConfigInfo(configData);
        updateDiskStatus();
        showMessage('配置文件加载成功', 'success');
        hideFileGuideModal()
    } catch (error) {
        console.error('文件处理错误:', error);
        showMessage('配置文件处理失败: ' + error.message, 'error')
    }
}
async function processConfigFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = event => {
            try {
                const arrayBuffer = event.target.result;
                if (arrayBuffer.byteLength !== 0x400) {
                    throw new Error(`文件大小不正确，应为1024字节(0x400)，实际为${arrayBuffer.byteLength}字节`)
                }
                resolve(new Uint8Array(arrayBuffer))
            } catch (error) {
                reject(error)
            }
        };
        reader.onerror = error => reject(error);
        reader.readAsArrayBuffer(file)
    })
}
function parseConfigData(byteArray) {
    if (byteArray.length !== 0x400) {
        throw new Error(`无效的文件长度，应为1024字节(0x400)，实际为${byteArray.length}字节`)
    }
    const configIdBytes = byteArray.slice(0, 16)
    const configId = Array.from(configIdBytes).map(b => b.toString(16).padStart(2, '0')).join('')
    const freezedDisksView = new DataView(byteArray.buffer, 17, 4)
    const freezedDisks = freezedDisksView.getUint32(0, false)
    const freezeEnabled = byteArray[57] === 0x01
    const deviceIdBytes = byteArray.slice(85, 103)
    const deviceId = Array.from(deviceIdBytes).map(b => String.fromCharCode(b)).join('').replace(/\x00/g, '')
    const deviceCodeBytes = byteArray.slice(104, 108)
    const deviceCode = Array.from(deviceCodeBytes).map(b => String.fromCharCode(b)).join('').replace(/\x00/g, '');
    return {
        configId,
        freezedDisks,
        freezeEnabled,
        deviceId,
        deviceCode,
        rawData: byteArray
    }
}
function displayConfigInfo(config) {
    document.getElementById('device-id').textContent = config.deviceId || '未知';
    document.getElementById('device-code').textContent = config.deviceCode || '未知';
    document.getElementById('freeze-enabled').textContent = config.freezeEnabled ? '已启用' : '已禁用';
    const binary = config.freezedDisks.toString(2).padStart(32, '0')
    frozenDisks = [];
    for (let i = 0; i < 26; i++) {
        if (config.freezedDisks & 1 << i) {
            frozenDisks.push(String.fromCharCode(65 + i))
        }
    }
    document.getElementById('freeze-status').textContent = frozenDisks.length > 0 ? frozenDisks.join(',') + ' 已冻结' : '无冻结磁盘';
    configDisplay.classList.remove('hidden');
    noConfig.classList.add('hidden')
}
function isDiskFrozen(letter) {
    if (!configData) return !1;
    const bitIndex = letter.charCodeAt(0) - 65;
    return Boolean(configData.freezedDisks & 1 << bitIndex)
}
function renderDiskList() {
    diskListEl.innerHTML = '';
    diskList.forEach(letter => {
        const diskEl = document.createElement('div');
        diskEl.className = 'disk-card';
        diskEl.dataset.letter = letter;
        const isFrozen = isDiskFrozen(letter);
        diskEl.classList.add(isFrozen ? 'frozen' : 'unfrozen');
        diskEl.innerHTML = `
        <div class="disk-header">
            <div class="disk-letter">
                <i class="material-icons">storage</i>
                ${letter}:
            </div>
            <span class="disk-status ${isFrozen ? 'status-frozen' : 'status-unfrozen'}">${isFrozen ? '已冻结' : '未冻结'}</span>
        </div>
        <div class="disk-info">
            <div>类型: ${letter === 'C' ? '系统盘' : '数据盘'}</div>
            <div>状态: ${isFrozen ? '保护中' : '正常'}</div>
        </div>
        ${diskList.length > 1 && letter != 'C' ? `<button class="disk-remove" onclick="removeDisk('${letter}')" title="删除磁盘">×</button>` : ''}`;
        diskEl.addEventListener('click', e => {
            if (!e.target.classList.contains('disk-remove')) {
                toggleDiskSelection(letter)
            }
        });
        diskListEl.appendChild(diskEl)
    })
}
function addNewDisk() {
    if (diskList.length >= 24) {
        showMessage('已达到最大磁盘数量(24个)', 'error');
        return
    }
    const usedLetters = new Set(diskList); for (let i = 2; i < 24; i++) {
        const letter = String.fromCharCode(65 + i);
        if (!usedLetters.has(letter)) {
            diskList.push(letter);
            diskList.sort();
            renderDiskList();
            updateDiskCount();
            showMessage(`已添加磁盘 ${letter}:`, 'success');
            return
        }
    }
    showMessage('无法添加更多磁盘', 'error')
}
function removeDisk(letter) {
    if (diskList.length > 1) {
        diskList = diskList.filter(d => d !== letter);
        selectedDisks.delete(letter);
        renderDiskList();
        updateSelectionUI();
        updateDiskCount();
        showMessage(`已删除磁盘 ${letter}:`, 'success')
    }
}
function updateDiskCount() {
    diskCountInfo.textContent = `当前磁盘数: ${diskList.length}`
}
function updateDiskStatus() {
    renderDiskList()
}
function toggleDiskSelection(letter) {
    if (selectedDisks.has(letter)) {
        selectedDisks.delete(letter)
    } else {
        selectedDisks.add(letter)
    }
    updateSelectionUI()
}
function updateSelectionUI() {
    document.querySelectorAll('.disk-card').forEach(card => {
        const letter = card.dataset.letter;
        if (selectedDisks.has(letter)) {
            card.classList.add('selected')
        } else {
            card.classList.remove('selected')
        }
    });
    const count = selectedDisks.size;
    selectedCount.textContent = count;
    if (count > 0) {
        selectedInfo.style.display = 'block';
        freezeBtn.disabled = !1;
        unfreezeBtn.disabled = !1
    } else {
        selectedInfo.style.display = 'none';
        freezeBtn.disabled = !0;
        unfreezeBtn.disabled = !0
    }
}
function selectAllDisks() {
    diskList.forEach(letter => selectedDisks.add(letter));
    updateSelectionUI()
}
function clearSelection() {
    selectedDisks.clear();
    updateSelectionUI()
}
function toggleApiMode() {
    const isV0Mode = apiV0Mode.checked;
    if (isV0Mode) {
        showMessage('已启用 API v0 模式，兼容旧版客户端', 'info')
    } else {
        showMessage('已禁用 API v0 模式', 'info')
    }
}
function showMessage(text, type = 'info') {
    const messageEl = document.createElement('div');
    messageEl.className = `${type}-message`;
    messageEl.textContent = text;
    messageArea.innerHTML = '';
    messageArea.appendChild(messageEl);
    setTimeout(() => {
        if (messageEl.parentNode) {
            messageEl.remove()
        }
    }, 3000)
}

class FreezeAPI {
    constructor(url) {
        this.apibase = url;
    }
    executeReboot() {
        fetch(this.apibase + '/api/v1/excute_reboot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            mode: 'no-cors',
            body: '{}'
        });
    }
    executeProtectTry(disks) {
        fetch(this.apibase + '/api/v1/excute_protect_try', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            mode: 'no-cors',
            body: JSON.stringify({selectedDisks:disks})
        });
    }
    executeProtect(disks) {
        fetch(this.apibase + '/api/v1/excute_protect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            mode: 'no-cors',
            body: JSON.stringify({selectedDisks:disks})
        });
    }
    executeUnProtect(disks) {
        fetch(this.apibase + '/api/v1/excute_unprotect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            mode: 'no-cors',
            body: JSON.stringify({selectedDisks:disks})
        });
    }
    setVolume(vol) {
        fetch(this.apibase + `/set?vol=${vol}`, {
            method: 'GET',
            mode: 'no-cors'
        });
    }
}

// Initialize API instance
const freezeAPI = new FreezeAPI('http://127.0.0.1:6082');

// Show confirmation dialog
function showConfirmDialog(title, message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 3000; display: flex;
        align-items: center; justify-content: center;
    `;
    
    const dialog = document.createElement('div');
    dialog.style.cssText = `
        background: white; border-radius: 8px; padding: 24px;
        max-width: 400px; width: 90%; text-align: center;
    `;
    
    dialog.innerHTML = `
        <h4 style="margin: 0 0 16px 0; color: #333;">${title}</h4>
        <p style="margin: 0 0 24px 0; color: #666; line-height: 1.5;">${message}</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button id="confirm-btn" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored" style="background: #f44336;">
                确认
            </button>
            <button id="cancel-btn" class="mdl-button mdl-js-button mdl-button--raised">
                取消
            </button>
        </div>
    `;
    
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    dialog.querySelector('#confirm-btn').onclick = () => {
        document.body.removeChild(overlay);
        onConfirm();
    };
    
    dialog.querySelector('#cancel-btn').onclick = () => {
        document.body.removeChild(overlay);
    };
}

// Updated handleDiskAction function
function handleDiskAction(action) {
    if (selectedDisks.size === 0) return;
    
    const selectedArray = Array.from(selectedDisks);
    const actionText = action === 'freeze' ? '冻结' : '解冻';
    
    // Check freeze logic constraints
    if (action === 'freeze') {
        // If trying to freeze non-C drives, C must be frozen or selected
        const nonCDisks = selectedArray.filter(disk => disk !== 'C');
        const isCFrozen = isDiskFrozen('C');
        const isCSelected = selectedArray.includes('C');
        
        if (nonCDisks.length > 0 && !isCFrozen && !isCSelected) {
            showMessage('要冻结其他磁盘，必须先冻结 C: 盘', 'error');
            return;
        }
    } else {
        // If trying to unfreeze C, warn that all disks will be unfrozen
        if (selectedArray.includes('C')) {
            const frozenDisks = diskList.filter(disk => isDiskFrozen(disk));
            const otherFrozenDisks = frozenDisks.filter(disk => disk !== 'C');
            
            if (otherFrozenDisks.length > 0) {
                showConfirmDialog(
                    '解冻确认',
                    `解冻 C: 盘将同时解冻所有其他已冻结的磁盘 (${otherFrozenDisks.join(', ')})。确认操作吗？您的设备将重启。`,
                    () => performUnfreeze(selectedArray)
                );
                return;
            }
        }
    }
    
    // Show confirmation dialog
    const diskNames = selectedArray.join(', ');
    const confirmMessage = `确认${actionText}磁盘: ${diskNames}？您的设备将重启。`;
    
    showConfirmDialog(
        `${actionText}确认`,
        confirmMessage,
        () => {
            if (action === 'freeze') {
                performFreeze(selectedArray);
            } else {
                performUnfreeze(selectedArray);
            }
        }
    );
}

// Perform freeze operation
function performFreeze(selectedArray) {
    try {
        // Update local config data
        let newFreezedDisks = configData ? configData.freezedDisks : 0;
        selectedArray.forEach(letter => {
            const bitIndex = letter.charCodeAt(0) - 65;
            newFreezedDisks |= 1 << bitIndex;
        });
        
        if (configData) {
            configData.freezedDisks = newFreezedDisks;
            displayConfigInfo(configData);
        }
        selectedArray.forEach((letter, index, array) => {
            array[index] = letter.toLowerCase();
        });
        
        renderDiskList();
        
        // API calls
        if (!apiV0Mode.checked) freezeAPI.executeProtectTry(selectedArray);
        
        setTimeout(() => {
            if (apiV0Mode.checked) {
                freezeAPI.setVolume(newFreezedDisks);
            } else {
                freezeAPI.executeProtect(selectedArray);
            }
                
            setTimeout(() => {
                freezeAPI.executeReboot();
                showMessage(`正在${selectedArray.join(', ')}磁盘冻结并重启设备...`, 'info');
            }, 500);
        }, 500);
        
        const diskNames = selectedArray.join(', ');
        showMessage(`开始冻结磁盘: ${diskNames}`, 'success');
        clearSelection();
        
    } catch (error) {
        console.error('冻结操作失败:', error);
        showMessage('冻结操作失败: ' + error.message, 'error');
    }
}

// Perform unfreeze operation  
function performUnfreeze(selectedArray) {
    try {
        // Update local config data
        let newFreezedDisks = configData ? configData.freezedDisks : 0;
        
        // If C is being unfrozen, unfreeze all disks
        if (selectedArray.includes('C')) {
            newFreezedDisks = 0; // Unfreeze all
        } else {
            selectedArray.forEach(letter => {
                const bitIndex = letter.charCodeAt(0) - 65;
                newFreezedDisks &= ~(1 << bitIndex);
            });
        }
        
        const actualUnfreezeList = selectedArray.includes('C') ? 
            diskList.filter(disk => isDiskFrozen(disk)) : selectedArray;
        if (configData) {
            configData.freezedDisks = newFreezedDisks;
            displayConfigInfo(configData);
        }
        
        renderDiskList();
        
        // API calls
        
        actualUnfreezeList.forEach((letter, index, array) => {
            array[index] = letter.toLowerCase();
        });
        if (apiV0Mode.checked) {
            freezeAPI.setVolume(newFreezedDisks);
        } else {
            freezeAPI.executeUnProtect(actualUnfreezeList);
        }
        setTimeout(() => {
            freezeAPI.executeReboot();
            showMessage('正在解冻磁盘并重启设备...', 'info');
        }, 500);
        
        const diskNames = actualUnfreezeList.join(', ');
        showMessage(`开始解冻磁盘: ${diskNames}`, 'success');
        clearSelection();
        
    } catch (error) {
        console.error('解冻操作失败:', error);
        showMessage('解冻操作失败: ' + error.message, 'error');
    }
}

window.removeDisk = removeDisk;
apiV0Mode.checked = false;