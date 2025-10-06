import { useEffect, useState } from 'react';

interface ToastProps {
  message: string;
  type?: 'success' | 'error' | 'info';
  duration?: number;
  onClose: () => void;
}

export default function Toast({ message, type = 'success', duration = 5000, onClose }: ToastProps): JSX.Element {
  const [isVisible, setIsVisible] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsVisible(false);
      setTimeout(onClose, 300); // アニメーション完了後にコールバック実行
    }, duration);

    return () => clearTimeout(timer);
  }, [duration, onClose]);

  const getToastStyles = () => {
    const baseStyles = {
      position: 'fixed' as const,
      top: '20px',
      right: '20px',
      padding: '12px 20px',
      borderRadius: '8px',
      color: 'white',
      fontWeight: '500',
      fontSize: '14px',
      zIndex: 1000,
      transform: isVisible ? 'translateX(0)' : 'translateX(100%)',
      transition: 'transform 0.3s ease-in-out',
      boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
      maxWidth: '400px',
      wordWrap: 'break-word' as const,
    };

    switch (type) {
      case 'success':
        return { ...baseStyles, backgroundColor: '#10b981' };
      case 'error':
        return { ...baseStyles, backgroundColor: '#ef4444' };
      case 'info':
        return { ...baseStyles, backgroundColor: '#3b82f6' };
      default:
        return { ...baseStyles, backgroundColor: '#10b981' };
    }
  };

  return (
    <div style={getToastStyles()}>
      {type === 'success' && '✅ '}
      {type === 'error' && '❌ '}
      {type === 'info' && 'ℹ️ '}
      {message}
    </div>
  );
}
