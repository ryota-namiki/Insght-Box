import { useRef, useState, useCallback, useEffect, forwardRef, useImperativeHandle } from 'react';

interface CameraCaptureProps {
  onCapture: (imageData: string) => void;
  onError: (error: string) => void;
}

export interface CameraCaptureRef {
  startCamera: () => void;
  stopCamera: () => void;
}

const CameraCapture = forwardRef<CameraCaptureRef, CameraCaptureProps>(({ onCapture, onError }, ref): JSX.Element => {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  
  const [isStreaming, setIsStreaming] = useState(false);
  const [isCaptured, setIsCaptured] = useState(false);
  const [capturedImage, setCapturedImage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // カメラストリームの開始
  const startCamera = useCallback(async () => {
    console.log('Starting camera...');
    try {
      setError(null);
      
      // 既存のストリームを停止
      if (streamRef.current) {
        console.log('Stopping existing stream');
        streamRef.current.getTracks().forEach(track => track.stop());
      }

      const constraints: MediaStreamConstraints = {
        video: {
          facingMode: 'environment', // 背面カメラを優先
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      };

      console.log('Requesting camera access with constraints:', constraints);
      const stream = await navigator.mediaDevices.getUserMedia(constraints);
      console.log('Camera access granted, stream:', stream);
      streamRef.current = stream;

      // ビデオ要素を表示
      setIsStreaming(true);
      
      // 次のレンダリングサイクルで videoRef が利用可能になるまで待機
      await new Promise(resolve => setTimeout(resolve, 100));
      
      // videoRefが利用可能になるまで待機
      let videoRetryCount = 0;
      const maxRetries = 20;
      
      while (!videoRef.current && videoRetryCount < maxRetries) {
        console.log(`Waiting for videoRef after render... attempt ${videoRetryCount + 1}, current:`, videoRef.current);
        await new Promise(resolve => setTimeout(resolve, 50));
        videoRetryCount++;
      }

      if (videoRef.current) {
        console.log('Setting video source object');
        videoRef.current.srcObject = stream;
        
        // ビデオの読み込みを強制的に開始
        videoRef.current.load();
      } else {
        console.log('videoRef.current is still null after retries!');
        stream.getTracks().forEach(track => track.stop());
        setError('ビデオ要素が利用できません。ページを再読み込みしてください。');
        onError('ビデオ要素が利用できません。ページを再読み込みしてください。');
        return;
      }
    } catch (err) {
      console.error('Camera access error:', err);
      const errorMessage = err instanceof Error ? err.message : 'カメラへのアクセスに失敗しました';
      setError(errorMessage);
      onError(errorMessage);
    }
  }, [onError]);

  // カメラストリームの停止
  const stopCamera = useCallback(() => {
    console.log('Stopping camera...');
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }
    setIsStreaming(false);
    setIsCaptured(false);
    setCapturedImage(null);
  }, []);

  // refを通じてメソッドを公開
  useImperativeHandle(ref, () => ({
    startCamera,
    stopCamera
  }), [startCamera, stopCamera]);

  // 撮影処理
  const capturePhoto = useCallback(() => {
    if (!videoRef.current || !canvasRef.current) {
      console.log('Video or canvas not available');
      return;
    }

    const video = videoRef.current;
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    
    if (!ctx) {
      onError('Canvas context not available');
      return;
    }

    // ビデオのサイズに合わせてキャンバスを設定
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // ビデオフレームをキャンバスに描画
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // 画像データを取得
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    
    console.log('Photo captured successfully');
    setCapturedImage(imageData);
    setIsCaptured(true);
    
    // 撮影後もカメラは停止しない
    onCapture(imageData);
  }, [onCapture, onError, stopCamera]);

  // 再撮影
  const retakePhoto = useCallback(() => {
    setCapturedImage(null);
    setIsCaptured(false);
    // 再撮影時はカメラを再度起動（既に起動している場合は再起動）
    if (!isStreaming) {
      startCamera();
    }
  }, [startCamera, isStreaming]);

  // コンポーネントのクリーンアップ
  useEffect(() => {
    return () => {
      stopCamera();
    };
  }, [stopCamera]);

  return (
    <div style={{ 
      border: '2px dashed #ccc', 
      borderRadius: 8, 
      padding: 20, 
      backgroundColor: '#fafafa',
      textAlign: 'center'
    }}>
      
      {error && (
        <div style={{ 
          marginBottom: 16, 
          padding: 12, 
          backgroundColor: '#fef2f2', 
          border: '1px solid #fecaca', 
          borderRadius: 8,
          color: '#dc2626'
        }}>
          <p style={{ margin: 0, fontSize: '0.9rem' }}>{error}</p>
        </div>
      )}

      {/* カメラコントロール */}
      <div style={{ marginBottom: 16 }}>
        {isStreaming && (
          <div>
            <button
              type="button"
              className="button"
              onClick={stopCamera}
              style={{ marginRight: 8 }}
            >
              🛑 カメラを停止
            </button>
            <button
              type="button"
              className="button button-primary"
              onClick={capturePhoto}
              disabled={isCaptured}
              style={{ 
                opacity: isCaptured ? 0.5 : 1,
                cursor: isCaptured ? 'not-allowed' : 'pointer'
              }}
            >
              📸 撮影
            </button>
            {isCaptured && (
              <button
                type="button"
                className="button"
                onClick={retakePhoto}
                style={{ marginLeft: 8 }}
              >
                🔄 再撮影
              </button>
            )}
          </div>
        )}
      </div>

      {/* ビデオ表示 */}
      {isStreaming && !isCaptured && (
        <div style={{ 
          border: '1px solid #e5e7eb',
          borderRadius: 8,
          overflow: 'hidden',
          backgroundColor: '#000',
          marginBottom: 16,
          width: '100%',
          maxWidth: '600px',
          margin: '0 auto 16px auto'
        }}>
          <video
            ref={videoRef}
            autoPlay
            playsInline
            muted
            style={{
              width: '100%',
              height: 'auto',
              display: 'block',
              minHeight: '300px'
            }}
          />
        </div>
      )}

      {/* 撮影した画像のプレビュー */}
      {capturedImage && (
        <div style={{ 
          border: '1px solid #e5e7eb',
          borderRadius: 8,
          overflow: 'hidden',
          backgroundColor: '#f9fafb',
          marginBottom: 16,
          width: '100%',
          maxWidth: '600px',
          margin: '0 auto 16px auto'
        }}>
          <img 
            src={capturedImage} 
            alt="撮影した画像" 
            style={{ 
              width: '100%',
              height: 'auto',
              display: 'block',
              minHeight: '300px',
              objectFit: 'contain'
            }}
          />
        </div>
      )}

      {/* 隠しキャンバス（撮影用） */}
      <canvas
        ref={canvasRef}
        style={{ display: 'none' }}
      />
    </div>
  );
});

CameraCapture.displayName = 'CameraCapture';

export default CameraCapture;
