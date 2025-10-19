/**
 * Password Strength Indicator
 * Hiển thị độ mạnh của password real-time
 */

class PasswordStrengthIndicator {
    constructor(options = {}) {
        this.container = options.container || document.getElementById('password-strength-container');
        this.passwordInput = options.passwordInput || document.getElementById('password');
        this.minLength = options.minLength || 8;
        this.requireUppercase = options.requireUppercase !== false;
        this.requireLowercase = options.requireLowercase !== false;
        this.requireNumbers = options.requireNumbers !== false;
        this.requireSpecialChars = options.requireSpecialChars !== false;
        
        this.init();
    }

    init() {
        if (!this.passwordInput || !this.container) {
            console.error('Password input or container not found');
            return;
        }

        this.createIndicator();
        this.bindEvents();
    }

    createIndicator() {
        // Tạo HTML cho indicator
        this.container.innerHTML = `
            <div class="password-strength">
                <div class="strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                </div>
                <div class="strength-text" id="strength-text">Nhập mật khẩu</div>
                <div class="strength-requirements" id="strength-requirements">
                    <div class="requirement" data-requirement="length">
                        <i class="icon"></i>
                        <span>Ít nhất ${this.minLength} ký tự</span>
                    </div>
                    <div class="requirement" data-requirement="uppercase">
                        <i class="icon"></i>
                        <span>Chữ hoa</span>
                    </div>
                    <div class="requirement" data-requirement="lowercase">
                        <i class="icon"></i>
                        <span>Chữ thường</span>
                    </div>
                    <div class="requirement" data-requirement="numbers">
                        <i class="icon"></i>
                        <span>Số</span>
                    </div>
                    <div class="requirement" data-requirement="special">
                        <i class="icon"></i>
                        <span>Ký tự đặc biệt</span>
                    </div>
                </div>
            </div>
        `;

        // Thêm CSS
        this.addStyles();
    }

    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .password-strength {
                margin-top: 10px;
                font-size: 14px;
            }

            .strength-bar {
                width: 100%;
                height: 8px;
                background-color: #e0e0e0;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 8px;
            }

            .strength-fill {
                height: 100%;
                width: 0%;
                transition: all 0.3s ease;
                border-radius: 4px;
            }

            .strength-fill.very-weak { background-color: #ff4444; width: 20%; }
            .strength-fill.weak { background-color: #ff8800; width: 40%; }
            .strength-fill.medium { background-color: #ffbb00; width: 60%; }
            .strength-fill.strong { background-color: #88cc00; width: 80%; }
            .strength-fill.very-strong { background-color: #00aa00; width: 100%; }

            .strength-text {
                font-weight: bold;
                margin-bottom: 8px;
            }

            .strength-text.very-weak { color: #ff4444; }
            .strength-text.weak { color: #ff8800; }
            .strength-text.medium { color: #ffbb00; }
            .strength-text.strong { color: #88cc00; }
            .strength-text.very-strong { color: #00aa00; }

            .strength-requirements {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .requirement {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 12px;
            }

            .requirement .icon {
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background-color: #e0e0e0;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }

            .requirement .icon::before {
                content: '✗';
                color: #666;
                font-size: 10px;
            }

            .requirement.valid .icon {
                background-color: #4caf50;
            }

            .requirement.valid .icon::before {
                content: '✓';
                color: white;
            }

            .requirement.valid {
                color: #4caf50;
            }
        `;
        document.head.appendChild(style);
    }

    bindEvents() {
        this.passwordInput.addEventListener('input', (e) => {
            this.updateStrength(e.target.value);
        });

        this.passwordInput.addEventListener('blur', (e) => {
            this.updateStrength(e.target.value);
        });
    }

    updateStrength(password) {
        const analysis = this.analyzePassword(password);
        this.updateStrengthBar(analysis);
        this.updateRequirements(analysis);
    }

    analyzePassword(password) {
        const analysis = {
            length: password.length >= this.minLength,
            uppercase: this.requireUppercase ? /[A-Z]/.test(password) : true,
            lowercase: this.requireLowercase ? /[a-z]/.test(password) : true,
            numbers: this.requireNumbers ? /[0-9]/.test(password) : true,
            special: this.requireSpecialChars ? /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password) : true,
            score: 0,
            level: 'very-weak'
        };

        // Tính điểm
        if (analysis.length) analysis.score += 20;
        if (analysis.uppercase) analysis.score += 15;
        if (analysis.lowercase) analysis.score += 15;
        if (analysis.numbers) analysis.score += 15;
        if (analysis.special) analysis.score += 20;

        // Điểm cho độ dài
        if (password.length >= 12) analysis.score += 10;
        if (password.length >= 16) analysis.score += 5;

        // Điểm cho độ phức tạp
        if (analysis.uppercase && analysis.lowercase) analysis.score += 5;
        if (analysis.numbers && /[a-zA-Z]/.test(password)) analysis.score += 5;

        // Trừ điểm cho các vấn đề
        if (this.hasConsecutiveChars(password, 3)) analysis.score -= 10;
        if (this.hasRepeatingChars(password, 3)) analysis.score -= 10;
        if (this.isCommonPassword(password)) analysis.score -= 30;

        // Xác định mức độ
        if (analysis.score < 30) analysis.level = 'very-weak';
        else if (analysis.score < 50) analysis.level = 'weak';
        else if (analysis.score < 70) analysis.level = 'medium';
        else if (analysis.score < 90) analysis.level = 'strong';
        else analysis.level = 'very-strong';

        return analysis;
    }

    updateStrengthBar(analysis) {
        const fill = document.getElementById('strength-fill');
        const text = document.getElementById('strength-text');

        if (!fill || !text) return;

        // Reset classes
        fill.className = 'strength-fill';
        text.className = 'strength-text';

        // Add level class
        fill.classList.add(analysis.level);
        text.classList.add(analysis.level);

        // Update text
        const messages = {
            'very-weak': 'Rất yếu - Cần cải thiện ngay',
            'weak': 'Yếu - Nên thêm ký tự đặc biệt và số',
            'medium': 'Trung bình - Có thể cải thiện thêm',
            'strong': 'Mạnh - Mật khẩu tốt',
            'very-strong': 'Rất mạnh - Mật khẩu xuất sắc'
        };

        text.textContent = messages[analysis.level] || 'Nhập mật khẩu';
    }

    updateRequirements(analysis) {
        const requirements = document.querySelectorAll('.requirement');
        
        requirements.forEach(req => {
            const type = req.dataset.requirement;
            const icon = req.querySelector('.icon');
            
            if (analysis[type]) {
                req.classList.add('valid');
            } else {
                req.classList.remove('valid');
            }
        });
    }

    hasConsecutiveChars(password, maxConsecutive) {
        let consecutive = 1;
        for (let i = 1; i < password.length; i++) {
            if (password.charCodeAt(i) === password.charCodeAt(i-1) + 1) {
                consecutive++;
                if (consecutive > maxConsecutive) {
                    return true;
                }
            } else {
                consecutive = 1;
            }
        }
        return false;
    }

    hasRepeatingChars(password, maxRepeating) {
        let repeating = 1;
        for (let i = 1; i < password.length; i++) {
            if (password[i] === password[i-1]) {
                repeating++;
                if (repeating > maxRepeating) {
                    return true;
                }
            } else {
                repeating = 1;
            }
        }
        return false;
    }

    isCommonPassword(password) {
        const commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'root', 'user', 'test',
            '12345678', 'welcome', 'monkey', 'dragon', 'master',
            'hello', 'login', 'pass', '1234', '12345',
            'matkhau', 'admin123', 'user123', 'test123'
        ];
        
        return commonPasswords.includes(password.toLowerCase());
    }

    // API để lấy thông tin strength
    getStrengthInfo(password) {
        return this.analyzePassword(password);
    }

    // API để validate password
    validatePassword(password) {
        const analysis = this.analyzePassword(password);
        return {
            valid: analysis.length && analysis.uppercase && analysis.lowercase && 
                   analysis.numbers && analysis.special && analysis.score >= 50,
            analysis: analysis
        };
    }
}

// Auto-initialize nếu có element với id 'password-strength-container'
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('password-strength-container');
    const passwordInput = document.getElementById('password');
    
    if (container && passwordInput) {
        new PasswordStrengthIndicator({
            container: container,
            passwordInput: passwordInput
        });
    }
});

// Export cho sử dụng module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PasswordStrengthIndicator;
}
