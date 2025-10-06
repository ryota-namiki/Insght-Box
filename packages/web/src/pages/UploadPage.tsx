import { FormEvent, useEffect, useMemo, useState, useCallback, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useDropzone } from 'react-dropzone';
import type { EventMeta, Tag } from '@insight-box/core';
import { createCard, getCards, getEvents, createDocumentFromFile, createDocument, getJob, getDocumentV1, getDocumentText, type CardSummary, type DocumentMeta, type DocumentSource } from '../api/client';
import CameraCapture, { CameraCaptureRef } from '../components/CameraCapture';

interface UploadFormState {
  title: string;
  companyName: string;
  eventId: string;
  ocrText: string;
  tagsInput: string;
  memo: string;
  webclipUrl: string;
  webclipContent: string;
  webclipDescription: string;
}

type UploadMode = 'manual' | 'file' | 'webclip' | 'camera';

const initialForm: UploadFormState = {
  title: '',
  companyName: '',
  eventId: '',
  ocrText: '',
  tagsInput: '',
  memo: '',
  webclipUrl: '',
  webclipContent: '',
  webclipDescription: '',
};

export default function UploadPage(): JSX.Element {
  const navigate = useNavigate();
  const [form, setForm] = useState<UploadFormState>(initialForm);
  const [events, setEvents] = useState<EventMeta[]>([]);
  const [cards, setCards] = useState<CardSummary[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [uploadMode, setUploadMode] = useState<UploadMode>('manual');
  const [uploadedFile, setUploadedFile] = useState<File | null>(null);
  const [filePreviewUrl, setFilePreviewUrl] = useState<string | null>(null);
  const [extractedText, setExtractedText] = useState<string>('');
  const [isProcessingOCR, setIsProcessingOCR] = useState<boolean>(false);
  const [isPDFAnalysis, setIsPDFAnalysis] = useState<boolean>(false);
  const [currentJobId, setCurrentJobId] = useState<string | null>(null);
  const [currentDocumentId, setCurrentDocumentId] = useState<string | null>(null);
  const extractedTextRef = useRef<string>('');
  const [jobStatus, setJobStatus] = useState<string>('');
  const [jobProgress, setJobProgress] = useState<number>(0);
  const [capturedImage, setCapturedImage] = useState<string | null>(null);
  const cameraRef = useRef<CameraCaptureRef>(null);

  useEffect(() => {
    void loadInitialData();
  }, []);

  // プレビューURLのクリーンアップ
  useEffect(() => {
    return () => {
      if (filePreviewUrl) {
        URL.revokeObjectURL(filePreviewUrl);
      }
    };
  }, [filePreviewUrl]);

  async function loadInitialData(): Promise<void> {
    try {
      const [eventList, cardList] = await Promise.all([getEvents(), getCards()]);
      setEvents(eventList);
      setCards(cardList);
      if (!form.eventId && eventList.length) {
        setForm((prev) => ({ ...prev, eventId: eventList[0]!.id }));
      }
    } catch (err) {
      setError((err as Error).message);
    }
  }

  const tags: Tag[] = useMemo(() => (
    form.tagsInput
      .split(',')
      .map((label) => label.trim())
      .filter(Boolean)
      .slice(0, 20)
      .map((label, index) => ({ id: `${label.toLowerCase().replace(/\s+/g, '-')}-${index}`, label }))
  ), [form.tagsInput]);

  const handleChange = (field: keyof UploadFormState) => (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setForm((prev) => ({ ...prev, [field]: event.target.value }));
  };

  // カメラ撮影完了時のハンドラー
  const handleCameraCapture = useCallback((imageData: string) => {
    setCapturedImage(imageData);
    setUploadMode('camera');
    setError(null);
    setSuccessMessage('撮影が完了しました！');
  }, []);

  // カメラエラー時のハンドラー
  const handleCameraError = useCallback((error: string) => {
    setError(error);
  }, []);


  const onDrop = useCallback(async (acceptedFiles: File[]) => {
    if (acceptedFiles.length > 0) {
      const file = acceptedFiles[0];
      
      setUploadedFile(file);
      setUploadMode('file');
      setExtractedText(''); // 新しいファイルが選択されたら抽出テキストをクリア
      setIsPDFAnalysis(file.type === 'application/pdf');
      setError(null);
      setSuccessMessage(null);
      
      // ファイルプレビューURLを生成（画像の場合のみ）
      if (file.type.startsWith('image/')) {
        const previewUrl = URL.createObjectURL(file);
        setFilePreviewUrl(previewUrl);
      } else {
        setFilePreviewUrl(null);
      }
      
      // 自動でテキスト抽出を実行
      setIsProcessingOCR(true);
      setJobProgress(0); // 進捗をリセット
      
      try {
        // 新しい統一APIを使用
        const tasks = file.type === 'application/pdf' ? ['pdf_analyze'] : ['ocr'];
        const meta: DocumentMeta = {
          expo: 'CEATEC 2025', // デフォルト値
          booth: 'ACME', // デフォルト値
          captured_at: new Date().toISOString(),
          device: 'web'
        };

        const result = await createDocumentFromFile(file, tasks, meta, {
          enableOCR: file.type === 'application/pdf'
        });
        console.log('ドキュメント作成結果:', result);

        setCurrentJobId(result.job_id);
        setCurrentDocumentId(result.document_id);
        setJobStatus('queued');

        // ジョブの完了を待つ
        await waitForJobCompletion(result.job_id);

      } catch (error) {
        console.error('テキスト抽出エラー:', error);
        setExtractedText(`テキスト抽出に失敗しました: ${(error as Error).message}`);
        setIsProcessingOCR(false);
        setCurrentJobId(null);
        setError((error as Error).message);
      }
    }
  }, []); // 依存配列を空にして、関数が再作成されないようにする

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    accept: {
      'image/*': ['.jpeg', '.jpg', '.png', '.gif'],
      'application/pdf': ['.pdf'],
      'text/plain': ['.txt']
    },
    maxFiles: 1,
    maxSize: 10 * 1024 * 1024 // 10MB
  });

  // ジョブの完了を待つ関数
  const waitForJobCompletion = async (jobId: string) => {
    const maxAttempts = 120; // 最大120回（60秒、0.5秒間隔）
    let attempts = 0;

    const checkJob = async () => {
      try {
        const job = await getJob(jobId);
        setJobStatus(job.status);
        setJobProgress(job.progress);

        if (job.status === 'succeeded') {
          // ドキュメントの内容を取得
          if (job.document_id) {
            const textResponse = await getDocumentText(job.document_id);
            const extractedTextValue = textResponse.text || '';
            
            setExtractedText(extractedTextValue);
            extractedTextRef.current = extractedTextValue;
            setCurrentDocumentId(job.document_id);
          }
          // ジョブ完了時にローディング状態を解除
          setIsProcessingOCR(false);
          setCurrentJobId(null);
          setError(null);
          return;
        } else if (job.status === 'failed') {
          setError('処理中にエラーが発生しました');
          setIsProcessingOCR(false);
          setCurrentJobId(null);
          return;
        }

        // まだ処理中の場合は再試行
        attempts++;
        if (attempts < maxAttempts) {
          setTimeout(checkJob, 500); // 0.5秒後に再試行
        } else {
          setError('処理がタイムアウトしました');
          setIsProcessingOCR(false);
          setCurrentJobId(null);
        }
      } catch (error) {
        console.error('ジョブ確認エラー:', error);
        setError(`ジョブの確認中にエラーが発生しました: ${(error as Error).message}`);
        setIsProcessingOCR(false);
        setCurrentJobId(null);
      }
    };

    checkJob();
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    try {
      let response;
      
      if (uploadMode === 'file' && uploadedFile) {
        // ファイルアップロード済みのテキストを使用してカード作成
        response = await createCard({
          title: form.title,
          companyName: form.companyName || undefined,
          eventId: form.eventId,
          ocrText: extractedText, // 既に抽出済みのテキストを使用
          tags,
          memo: form.memo,
          documentId: currentDocumentId || undefined,
        });
        // カード詳細ページに遷移（トーストメッセージ付き）
        navigate(`/card/${response.card.id}?toast=${encodeURIComponent('カード作成が完了しました！')}&toastType=success`);
      } else if (uploadMode === 'camera' && capturedImage) {
        // カメラ撮影の画像を使用してカード作成
        const cardData = {
          title: form.title,
          companyName: form.companyName || undefined,
          eventId: form.eventId,
          ocrText: form.ocrText, // 手動入力のテキストを使用
          tags,
          memo: form.memo,
          // カメラ撮影の画像データをメモに追加
          cameraImage: capturedImage,
        };
        
        console.log('Creating camera card with data:', cardData);
        console.log('Memo value:', form.memo);
        console.log('Memo type:', typeof form.memo);
        console.log('Memo length:', form.memo?.length);
        
        response = await createCard(cardData);
        // カード詳細ページに遷移（トーストメッセージ付き）
        navigate(`/card/${response.card.id}?toast=${encodeURIComponent('カメラ撮影カード作成が完了しました！')}&toastType=success`);
      } else if (uploadMode === 'webclip') {
        // Webクリップ処理（Laravel API対応）
        const source: DocumentSource = {
          type: 'url',
          url: form.webclipUrl
        };
        const meta: DocumentMeta = {
          expo: 'CEATEC 2025', // デフォルト値
          booth: 'ACME', // デフォルト値
          captured_at: new Date().toISOString(),
          device: 'web'
        };

        const result = await createDocument(source, ['web_clip'], meta);
        setCurrentJobId(result.job_id);
        setCurrentDocumentId(result.document_id);
        setJobStatus('queued');

        // ジョブの完了を待つ
        await waitForJobCompletion(result.job_id);

        // テキスト抽出が完了したので、抽出されたテキストを取得
        const textResult = await getDocumentText(result.document_id);
        const webClipText = textResult.text;

        // カードを作成
        response = await createCard({
          title: form.title,
          companyName: form.companyName || undefined,
          eventId: form.eventId,
          ocrText: webClipText,
          tags,
          memo: form.memo,
          documentId: result.document_id,
        });
        
        setSuccessMessage(`Webクリップを作成しました: ${form.title}`);
        // カード詳細ページに遷移（トーストメッセージ付き）
        navigate(`/card/${response.card.id}?toast=${encodeURIComponent('Webクリップが完了しました！')}&toastType=success`);
      } else {
        // 手動入力
        response = await createCard({
          title: form.title,
          companyName: form.companyName || undefined,
          eventId: form.eventId,
          ocrText: form.ocrText,
          tags,
          memo: form.memo,
        });
        // カード詳細ページに遷移（トーストメッセージ付き）
        navigate(`/card/${response.card.id}?toast=${encodeURIComponent('カード作成が完了しました！')}&toastType=success`);
      }
      
      setForm((prev) => ({ ...initialForm, eventId: prev.eventId }));
      const newCard: CardSummary = {
        ...response.summary,
        id: response.summary.id
      };
      setCards((prev) => [newCard, ...prev]);
      setUploadedFile(null);
      setUploadMode('manual');
      setExtractedText('');
      setCapturedImage(null);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
      setIsProcessingOCR(false);
    }
  };

  return (
    <div className="grid" style={{ gap: 24 }}>
      <section className="section-card">
        <h2 className="section-title">クイックアップロード</h2>
        
        {/* アップロードモード選択 */}
        <div style={{ display: 'flex', gap: 12, marginBottom: 16, flexWrap: 'wrap' }}>
          <button
            type="button"
            className={`button ${uploadMode === 'manual' ? 'button-primary' : ''}`}
            onClick={() => {
              setUploadMode('manual');
              setExtractedText('');
              setCapturedImage(null);
            }}
          >
            手動入力
          </button>
          <button
            type="button"
            className={`button ${uploadMode === 'file' ? 'button-primary' : ''}`}
            onClick={() => {
              setUploadMode('file');
              setExtractedText('');
              setCapturedImage(null);
            }}
          >
            ファイルアップロード
          </button>
          <button
            type="button"
            className={`button ${uploadMode === 'camera' ? 'button-primary' : ''}`}
            onClick={() => {
              setUploadMode('camera');
              setExtractedText('');
              setCapturedImage(null);
              // カメラ撮影モードに切り替えた時に自動でカメラを起動
              setTimeout(() => {
                if (cameraRef.current && cameraRef.current.startCamera) {
                  cameraRef.current.startCamera();
                }
              }, 100);
            }}
          >
            📷 カメラ撮影
          </button>
          <button
            type="button"
            className={`button ${uploadMode === 'webclip' ? 'button-primary' : ''}`}
            onClick={() => {
              setUploadMode('webclip');
              setExtractedText('');
              setCapturedImage(null);
            }}
          >
            Webクリップ
          </button>
        </div>

        <form onSubmit={handleSubmit} className="grid" style={{ gap: 12 }}>
          <input
            className="input"
            placeholder="タイトル"
            value={form.title}
            onChange={handleChange('title')}
            required
            maxLength={120}
          />
          <input
            className="input"
            placeholder="企業名 (任意)"
            value={form.companyName}
            onChange={handleChange('companyName')}
          />
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
            <select className="select" value={form.eventId} onChange={handleChange('eventId')} required style={{ flex: 1 }}>
              <option value="" disabled>
                イベントを選択
              </option>
              {events.map((event) => (
                <option key={event.id} value={event.id}>
                  {event.name}
                </option>
              ))}
            </select>
            <Link to="/events" className="button" style={{ textDecoration: 'none', whiteSpace: 'nowrap' }}>
              📅 イベント管理
            </Link>
          </div>

          {/* ファイルアップロードエリア */}
          {uploadMode === 'file' && (
            <div
              {...getRootProps()}
              style={{
                border: '2px dashed #ccc',
                borderRadius: 8,
                padding: 20,
                textAlign: 'center',
                cursor: 'pointer',
                backgroundColor: isDragActive ? '#f0f0f0' : '#fafafa',
                borderColor: isDragActive ? '#007bff' : '#ccc'
              }}
            >
              <input {...getInputProps()} />
              {uploadedFile ? (
                <div>
                  <p>✅ 選択されたファイル: {uploadedFile.name}</p>
                  <p style={{ fontSize: '0.9rem', color: '#666' }}>
                    ({Math.round(uploadedFile.size / 1024)}KB)
                  </p>
                  {(isProcessingOCR || currentJobId) && (
                    <div style={{ 
                      display: 'flex', 
                      alignItems: 'center', 
                      justifyContent: 'center',
                      gap: '8px',
                      marginTop: '10px'
                    }}>
                      <div className="spinner" style={{
                        width: '16px',
                        height: '16px',
                        border: '2px solid #f3f3f3',
                        borderTop: '2px solid #007bff',
                        borderRadius: '50%',
                        animation: 'spin 1s linear infinite'
                      }}></div>
                      <p style={{ fontSize: '0.9rem', color: '#007bff', margin: 0 }}>
                        {isPDFAnalysis ? 'PDF分析中...' : 'テキスト抽出中...'}
                      </p>
                    </div>
                  )}
                  {extractedText && !isProcessingOCR && (
                    <p style={{ fontSize: '0.9rem', color: '#059669' }}>
                      ✅ {isPDFAnalysis ? 'PDF分析完了' : 'テキスト抽出完了'}
                    </p>
                  )}
                </div>
              ) : (
                <div>
                  <p>ファイルをドラッグ&ドロップまたはクリックして選択</p>
                  <p style={{ fontSize: '0.9rem', color: '#666' }}>
                    対応形式: 画像 (JPEG, PNG, GIF), PDF, テキストファイル
                  </p>
                  <p style={{ fontSize: '0.8rem', color: '#888' }}>
                    ※ ファイル選択後、自動でテキスト抽出・PDF分析が実行されます
                  </p>
                </div>
              )}
            </div>
          )}

          {/* カメラ撮影エリア */}
          {uploadMode === 'camera' && (
            <div style={{ marginBottom: 16 }}>
              <CameraCapture
                ref={cameraRef}
                onCapture={handleCameraCapture}
                onError={handleCameraError}
              />
              {capturedImage && (
                <div style={{ 
                  marginTop: 16, 
                  border: '1px solid #e5e7eb',
                  borderRadius: 8,
                  overflow: 'hidden',
                  backgroundColor: '#f9fafb',
                  width: '100%',
                  maxWidth: '600px',
                  margin: '16px auto 0 auto'
                }}>
                  <h3 style={{ margin: '12px 16px', color: '#374151', fontSize: '1rem' }}>撮影画像プレビュー</h3>
                  <img 
                    src={capturedImage} 
                    alt="撮影した画像" 
                    style={{ 
                      width: '100%',
                      height: 'auto',
                      display: 'block',
                      objectFit: 'contain'
                    }}
                  />
                </div>
              )}
            </div>
          )}

          {/* Webクリップ入力 */}
          {uploadMode === 'webclip' && (
            <>
              <input
                className="input"
                placeholder="URL"
                value={form.webclipUrl}
                onChange={handleChange('webclipUrl')}
                required
              />
              <textarea
                className="textarea"
                rows={3}
                placeholder="Webクリップの説明（任意）"
                value={form.webclipDescription}
                onChange={handleChange('webclipDescription')}
              />
              <textarea
                className="textarea"
                rows={4}
                placeholder="Webページの内容（任意）"
                value={form.webclipContent}
                onChange={handleChange('webclipContent')}
              />
            </>
          )}

          {/* OCRテキスト表示エリア */}
          
          {/* ファイルプレビュー */}
          {filePreviewUrl && uploadedFile && (
            <div style={{ marginBottom: 16 }}>
              <h3 style={{ fontSize: '1rem', marginBottom: 8, color: '#374151' }}>📷 アップロードされた画像</h3>
              <div style={{ 
                border: '1px solid #e5e7eb',
                borderRadius: 8,
                padding: 16,
                backgroundColor: '#f9fafb',
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
              }}>
                <img 
                  src={filePreviewUrl} 
                  alt="アップロードされたファイル" 
                  style={{ 
                    maxWidth: '100%',
                    maxHeight: '400px',
                    objectFit: 'contain',
                    borderRadius: 4,
                  }}
                />
              </div>
              <p style={{ fontSize: '0.85rem', color: '#6b7280', marginTop: 8 }}>
                ファイル名: {uploadedFile.name} ({Math.round(uploadedFile.size / 1024)}KB)
              </p>
            </div>
          )}

          {uploadMode !== 'camera' && (
            <textarea
              className="textarea"
              rows={6}
              placeholder={isProcessingOCR ? "OCR処理中..." : "OCRテキスト / メモ"}
              value={
                uploadMode === 'file' ? (isProcessingOCR ? "" : extractedText) :
                form.ocrText
              }
              onChange={uploadMode === 'file' ? undefined : handleChange('ocrText')}
              readOnly={uploadMode === 'file'}
              style={{
                backgroundColor: uploadMode === 'file' ? '#f8f9fa' : 'white',
                color: uploadMode === 'file' ? '#495057' : 'inherit',
                fontFamily: 'monospace',
                fontSize: '0.9rem',
                lineHeight: '1.4',
                whiteSpace: 'pre-wrap',
                display: uploadMode === 'file' && isProcessingOCR ? 'none' : 'block'
              }}
            />
          )}
          {uploadMode === 'file' && extractedText && !isProcessingOCR && (
            <p style={{ fontSize: '0.85rem', color: '#6c757d', marginTop: 4 }}>
              ※ {isPDFAnalysis ? 'PDFから抽出・分析された情報です' : 'ファイルから抽出されたテキストです'}
            </p>
          )}
          <input
            className="input"
            placeholder="タグ (カンマ区切り)"
            value={form.tagsInput}
            onChange={handleChange('tagsInput')}
          />
          <textarea
            className="textarea"
            rows={2}
            placeholder="メモ"
            value={form.memo}
            onChange={handleChange('memo')}
          />
          <button className="button" type="submit" disabled={loading || isProcessingOCR}>
            {isProcessingOCR ? (isPDFAnalysis ? 'PDF分析中...' : 'OCR処理中...') :
              loading ? '保存中...' : 
              uploadMode === 'file' ? 'カードを作成' :
              uploadMode === 'camera' ? 'カードを作成' :
              uploadMode === 'webclip' ? 'Webクリップを作成' :
              'カードを作成'}
          </button>
          {error && <p style={{ color: '#dc2626' }}>{error}</p>}
          {successMessage && <p style={{ color: '#059669' }}>{successMessage}</p>}
          <div>
            {tags.map((tag) => (
              <span className="tag-pill" key={tag.id} style={{ marginRight: 8 }}>
                #{tag.label}
              </span>
            ))}
          </div>
        </form>
      </section>

      <section className="section-card">
        <h2 className="section-title">最近のカード</h2>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '16px', justifyContent: 'flex-start' }}>
          {cards.map((card) => (
            <article 
              key={card.id} 
              className="section-card" 
              style={{ 
                boxShadow: 'none', 
                border: '1px solid #e5e7eb',
                cursor: 'pointer',
                transition: 'all 0.2s ease',
                flex: '1 1 calc(33.333% - 11px)',
                minWidth: '300px',
                maxWidth: '50%',
                margin: 0,
              }}
              onClick={() => navigate(`/card/${card.id}`)}
              onMouseEnter={(e) => {
                e.currentTarget.style.borderColor = '#007bff';
                e.currentTarget.style.boxShadow = '0 2px 8px rgba(0, 123, 255, 0.1)';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.borderColor = '#e5e7eb';
                e.currentTarget.style.boxShadow = 'none';
              }}
            >
              <h3>{card.title}</h3>
              <p className={`badge status-${card.status}`}>{card.status}</p>
              <p style={{ color: '#6b7280', fontSize: '0.85rem' }}>{new Date(card.createdAt).toLocaleString()}</p>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {(card.tags ?? []).map((tag, index) => (
                  <span key={`${card.id}-${tag.id}-${index}`} className="tag-pill">
                    #{tag.label}
                  </span>
                ))}
              </div>
            </article>
          ))}
        </div>
      </section>
    </div>
  );
}
