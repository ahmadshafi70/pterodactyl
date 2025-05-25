@extends('layouts.admin')

@section('title')
    Creating a new S3 Server
@endsection

@section('content-header')
    <h1>Node Backup<small>Create a new S3 Server.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li><a href="{{ route('admin.node-backup.s3-server') }}">S3 Servers</a></li>
        <li class="active">New S3 Server</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="post">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Characteristics</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name" class="control-label">Name <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="name" autocomplete="off" name="name" class="form-control" value="{{ old('name') }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="pDescription" class="control-label">Description <span class="field-optional"></span></label>
                            <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="pDefaultRegion" class="control-label">Default Region <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pDefaultRegion" autocomplete="off" name="default_region" class="form-control" value="{{ old('default_region') }}"/>
                            </div>
                            <p class="text-muted small">The region to use when using the bucket.</p>
                        </div>
                        <div class="form-group">
                            <label for="pAccessKeyId" class="control-label">Access Key ID <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pAccessKeyId" autocomplete="off" name="access_key_id" class="form-control" value="{{ old('access_key_id') }}"/>
                            </div>
                            <p class="text-muted small">The access key ID of the user with access to the bucket.</p>
                        </div>
                        <div class="form-group">
                            <label for="pSecretAccessKey" class="control-label">Secret Access Key <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pSecretAccessKey" autocomplete="off" name="secret_access_key" class="form-control" value="{{ old('secret_access_key') }}"/>
                            </div>
                            <p class="text-muted small">The secret access key of the user with access to the bucket.</p>
                        </div>
                        <div class="form-group">
                            <label for="pBucket" class="control-label">Bucket <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pBucket" autocomplete="off" name="bucket" class="form-control" value="{{ old('bucket') }}"/>
                            </div>
                            <p class="text-muted small">The bucket to use for this server.</p>
                        </div>
                        <div class="form-group">
                            <label for="pEndpoint" class="control-label">Endpoint <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pEndpoint" autocomplete="off" name="endpoint" class="form-control" value="{{ old('endpoint') }}"/>
                            </div>
                            <p class="text-muted small">The endpoint to use for this server.</p>
                        </div>
                        <div class="form-group">
                            <label for="pMaxPartSize" class="control-label">Max Part Size <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pMaxPartSize" autocomplete="off" name="max_part_size" class="form-control" value="{{ old('max_part_size') ?? 5 * 1024 * 1024 * 1024 }}"/>
                            </div>
                            <p class="text-muted small">The maximum size of a part in bytes. The default one is 5 GiB so <code>5368709120</code> bytes as written in <a href="https://pterodactyl.io/panel/1.0/additional_configuration.html#multipart-upload" target="_blank">Pterodactyl documentation</a>.</p>
                        </div>
                        <div class="form-group">
                            <label for="pPresignedUrlLifespan" class="control-label">Presigned URL Lifespan <span class="field-required"></span></label>
                            <div>
                                <input type="text" id="pPresignedUrlLifespan" autocomplete="off" name="presigned_url_lifespan" class="form-control" value="{{ old('presigned_url_lifespan') ?? 60 }}"/>
                            </div>
                            <p class="text-muted small">The lifespan of a presigned URL in minutes. The default one is 60 minutes as written in <a href="https://pterodactyl.io/panel/1.0/additional_configuration.html#multipart-upload" target="_blank">Pterodactyl documentation</a>.</p>
                        </div>
                        <div class="form-group">
                            <label for="pUsePathStyleEndpoint" class="control-label">Use Path Style Endpoint <span class="field-required"></span></label>
                            <div>
                                <select name="use_path_style_endpoint" id="pUsePathStyleEndpoint" class="form-control">
                                    <option value="0" @if(old('use_path_style_endpoint') === '0') selected @endif>No</option>
                                    <option value="1" @if(old('use_path_style_endpoint') === '1') selected @endif>Yes</option>
                                </select>
                            </div>
                            <p class="text-muted small">As written in <a href="https://pterodactyl.io/panel/1.0/additional_configuration.html#using-s3-backups" target="_blank">Pterodactyl documentation</a>, for some configurations, you might have to change your S3 URL from <code>bucket.domain.com</code> to <code>domain.com/bucket</code>. To accomplish this, set this value to true.</p>
                        </div>
                        <div class="form-group">
                            <label for="pUseAccelerateEndpoint" class="control-label">Use Accelerate Endpoint <span class="field-required"></span></label>
                            <div>
                                <select name="use_accelerate_endpoint" id="pUseAccelerateEndpoint" class="form-control">
                                    <option value="0" @if(old('use_accelerate_endpoint') === '0') selected @endif>No</option>
                                    <option value="1" @if(old('use_accelerate_endpoint') === '1') selected @endif>Yes</option>
                                </select>
                            </div>
                            <p class="text-muted small">As written in <a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/transfer-acceleration-examples.html" target="_blank">Amazon S3 Docs</a>, when using their AWS S3 services, you can use their Transfer Acceleration system.</p>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="submit" value="Create S3-Server" class="btn btn-success btn-sm">
                        <a href="{{ route('admin.node-backup.s3-server') }}" class="btn btn-default btn-sm">Go Back</a>
                    </div>
                </div>
            </div>
        </form>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Informations</h3>
                </div>
                <div class="box-body">
                    <div class="text-center">
                        <button id="copyFromEnv" class="btn btn-primary btn-sm">Copy from .env</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        dataFromEnv = {
            'default_region': '{{ env('AWS_DEFAULT_REGION') }}',
            'access_key_id': '{{ env('AWS_ACCESS_KEY_ID') }}',
            'secret_access_key': '{{ env('AWS_SECRET_ACCESS_KEY') }}',
            'bucket': '{{ env('AWS_BACKUPS_BUCKET') }}',
            'endpoint': '{{ env('AWS_ENDPOINT') }}',
            'max_part_size': '{{ env('BACKUP_MAX_PART_SIZE') }}',
            'presigned_url_lifespan': '{{ env('BACKUP_PRESIGNED_URL_LIFESPAN') }}',
            'use_path_style_endpoint': @if (env('AWS_USE_PATH_STYLE_ENDPOINT')) '1' @else '0' @endif,
            'use_accelerate_endpoint': @if (env('AWS_BACKUPS_USE_ACCELERATE')) '1' @else '0' @endif,
        }

        $('#copyFromEnv').click(function() {
            $('#pDefaultRegion').val(dataFromEnv.default_region);
            $('#pAccessKeyId').val(dataFromEnv.access_key_id);
            $('#pSecretAccessKey').val(dataFromEnv.secret_access_key);
            $('#pBucket').val(dataFromEnv.bucket);
            $('#pEndpoint').val(dataFromEnv.endpoint);
            $('#pMaxPartSize').val(dataFromEnv.max_part_size);
            $('#pPresignedUrlLifespan').val(dataFromEnv.presigned_url_lifespan);
            $('#pUsePathStyleEndpoint').val(dataFromEnv.use_path_style_endpoint);
            $('#pUseAccelerateEndpoint').val(dataFromEnv.use_accelerate_endpoint);
        });
    </script>
@endsection
