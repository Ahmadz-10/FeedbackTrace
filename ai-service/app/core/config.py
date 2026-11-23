from pydantic import SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict

class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file='.env', env_file_encoding='utf-8', extra='ignore')
    app_name: str = 'FeedbackTrace AI Service'
    app_version: str = '0.1.0'
    groq_api_key: SecretStr | None = None
    groq_model: str = 'openai/gpt-oss-20b'
    embedding_model: str = 'intfloat/multilingual-e5-base'
    embedding_device: str = 'cpu'
    embedding_batch_size: int = 16
    service_api_key: SecretStr | None = None
    cors_origins: str = 'http://127.0.0.1:8000,http://localhost:8000'
    @property
    def cors_origin_list(self) -> list[str]:
        return [o.strip() for o in self.cors_origins.split(',') if o.strip()]
settings = Settings()
