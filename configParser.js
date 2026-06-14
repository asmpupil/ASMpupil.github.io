/**
 * 处理二进制配置文件
 * @param {File} file 用户选择的文件对象
 * @returns {Promise<Uint8Array>} 返回文件的二进制数据
 */
export async function processConfigFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = (event) => {
            try {
                const arrayBuffer = event.target.result;
                if (arrayBuffer.byteLength !== 0x400) {
                    throw new Error(`文件大小不正确，应为1024字节(0x400)，实际为${arrayBuffer.byteLength}字节`);
                }
                resolve(new Uint8Array(arrayBuffer));
            } catch (error) {
                reject(error);
            }
        };
        
        reader.onerror = (error) => reject(error);
        reader.readAsArrayBuffer(file);
    });
}

/**
 * 解析二进制配置数据
 * @param {Uint8Array} byteArray 二进制数据
 * @returns {Object} 解析后的配置对象
 */
export function parseConfigData(byteArray) {
    // 验证文件长度
    if (byteArray.length !== 0x400) {
        throw new Error(`无效的文件长度，应为1024字节(0x400)，实际为${byteArray.length}字节`);
    }

    // 1. 解析ConfigId (前16字节 → 32字符hex)
    const configIdBytes = byteArray.slice(0, 16);
    const configId = Array.from(configIdBytes)
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');

    // 2. 解析freezedDisks (偏移17-21，4字节 → 32位整数)
    const freezedDisksView = new DataView(byteArray.buffer, 17, 4);
    const freezedDisks = freezedDisksView.getUint32(0, false); // 小端序

    // 3. 解析freezeEnabled (偏移53，1字节 → boolean)
    const freezeEnabled = byteArray[53] === 0x01;

    // 4. 解析deviceId (偏移85-103，12字节 → ASCII字符串)
    const deviceIdBytes = byteArray.slice(85, 103);
    const deviceId = Array.from(deviceIdBytes)
        .map(b => String.fromCharCode(b))
        .join('')
        .replace(/\x00/g, ''); // 移除可能的空字符

    // 5. 解析deviceCode (偏移104-108，4字节 → ASCII字符串)
    const deviceCodeBytes = byteArray.slice(104, 108);
    const deviceCode = Array.from(deviceCodeBytes)
        .map(b => String.fromCharCode(b))
        .join('')
        .replace(/\x00/g, '');

    return {
        configId,
        freezedDisks,
        freezeEnabled,
        deviceId,
        deviceCode,
        rawData: byteArray,
        hexDump: Array.from(byteArray).slice(0, 109)
            .map(b => b.toString(16).padStart(2, '0'))
            .join(' ')
    };
}
